<?php

namespace App\Tests\Controller;

use App\Entity\Personal;
use App\Entity\Rol;
use App\Entity\Tienda;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class PersonalControllerTest extends WebTestCase
{
    private function crearUsuario(string $email): User
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $usuario = new User();
        $usuario->setEmail($email);
        $usuario->setPassword($hasher->hashPassword($usuario, 'secret123'));
        $entityManager->persist($usuario);
        $entityManager->flush();

        return $usuario;
    }

    private function crearTienda(User $usuario, string $nombre, bool $activa = true): Tienda
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $tienda = new Tienda();
        $tienda->setUsuario($usuario);
        $tienda->setNombre($nombre);
        $tienda->setActiva($activa);
        $entityManager->persist($tienda);
        $entityManager->flush();

        return $tienda;
    }

    private function crearRol(User $usuario, string $nombre, bool $activo = true): Rol
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $rol = new Rol();
        $rol->setUsuario($usuario);
        $rol->setNombre($nombre);
        $rol->setActivo($activo);
        $entityManager->persist($rol);
        $entityManager->flush();

        return $rol;
    }

    /**
     * @param list<Tienda> $tiendas
     * @param list<Rol>    $rolesOrdenados
     */
    private function crearPersonal(User $usuario, string $nombre, bool $activo = true, array $tiendas = [], array $rolesOrdenados = []): Personal
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $persona = new Personal();
        $persona->setUsuario($usuario);
        $persona->setNombre($nombre);
        $persona->setActivo($activo);
        foreach ($tiendas as $tienda) {
            $persona->addTienda($tienda);
        }
        $persona->reemplazarRolesEnOrden($rolesOrdenados);
        $entityManager->persist($persona);
        $entityManager->flush();

        return $persona;
    }

    private function login(KernelBrowser $client, string $email): void
    {
        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Entrar')->form();
        $form['_username'] = $email;
        $form['_password'] = 'secret123';
        $client->submit($form);
    }

    private function uniqueEmail(string $label): string
    {
        return $label.'_'.uniqid().'@example.com';
    }

    public function testSinSesionRedirigeALogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/personal');

        self::assertResponseRedirects('/login');
    }

    public function testListaPropiosYFiltraPorTiendaSinMostrarAjenos(): void
    {
        $client = static::createClient();

        $usuario = $this->crearUsuario($this->uniqueEmail('pers_list'));
        $tiendaCentro = $this->crearTienda($usuario, 'Sucursal Centro');
        $tiendaNorte = $this->crearTienda($usuario, 'Sucursal Norte');
        $rolGerente = $this->crearRol($usuario, 'Gerente titular');
        $rolCaja = $this->crearRol($usuario, 'Encargado de caja');

        $this->crearPersonal($usuario, 'Ana López', true, [$tiendaCentro], [$rolGerente, $rolCaja]);
        $this->crearPersonal($usuario, 'Luis Pérez', false, [$tiendaNorte], [$rolCaja]);
        $this->crearPersonal($this->crearUsuario($this->uniqueEmail('pers_ajeno')), 'Persona de otro');

        $this->login($client, $usuario->getEmail());
        $client->request('GET', '/personal');

        self::assertResponseIsSuccessful();
        $html = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('Ana López', $html);
        self::assertStringContainsString('Luis Pérez', $html);
        self::assertStringContainsString('desactivado', $html);
        self::assertStringContainsString('#1', $html);
        self::assertStringContainsString('Gerente titular', $html);
        self::assertStringNotContainsString('Persona de otro', $html);

        $client->request('GET', '/personal?tienda='.$tiendaCentro->getId());
        self::assertResponseIsSuccessful();
        $htmlFiltrado = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('Ana López', $htmlFiltrado);
        self::assertStringNotContainsString('Luis Pérez', $htmlFiltrado);
    }

    public function testCrearPersonalConMultiplesTiendasYRolesOrdenados(): void
    {
        $client = static::createClient();
        $usuario = $this->crearUsuario($this->uniqueEmail('pers_crea'));
        $tiendaA = $this->crearTienda($usuario, 'Centro');
        $tiendaB = $this->crearTienda($usuario, 'Sur');
        $rol1 = $this->crearRol($usuario, 'Gerente titular');
        $rol2 = $this->crearRol($usuario, 'Encargado de área');
        $rol3 = $this->crearRol($usuario, 'Cajero');
        $connection = static::getContainer()->get(EntityManagerInterface::class)->getConnection();

        $this->login($client, $usuario->getEmail());

        $crawler = $client->request('GET', '/personal/nuevo');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Guardar')->form();
        $form['personal[nombre]'] = 'María García';
        $form['personal[horasSemanales]'] = '40';
        $form['personal[fechaNacimiento]'] = '1994-05-12';
        $form['personal[finContrato]'] = '2027-01-31';
        $form['personal[tiendas]'][0]->tick();
        $form['personal[tiendas]'][1]->tick();
        $form['personal[rolesOrdenados]'] = $rol2->getId().','.$rol1->getId().','.$rol3->getId();
        $client->submit($form);

        self::assertResponseRedirects('/personal');
        $client->followRedirect();
        self::assertResponseIsSuccessful();

        $html = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('María García', $html);
        self::assertStringContainsString('40 h/sem', $html);

        $personaId = (int) $connection->fetchOne('SELECT id FROM personal WHERE usuario_id = ? AND nombre = ?', [$usuario->getId(), 'María García']);
        self::assertGreaterThan(0, $personaId);

        $tiendasGuardadas = $connection->fetchFirstColumn('SELECT tienda_id FROM personal_tienda WHERE personal_id = ? ORDER BY tienda_id ASC', [$personaId]);
        self::assertEqualsCanonicalizing([$tiendaA->getId(), $tiendaB->getId()], array_map('intval', $tiendasGuardadas));

        $rolesGuardados = $connection->fetchAllAssociative('SELECT rol_id, prioridad FROM personal_rol WHERE personal_id = ? ORDER BY prioridad ASC', [$personaId]);
        self::assertSame([
            ['rol_id' => $rol2->getId(), 'prioridad' => 1],
            ['rol_id' => $rol1->getId(), 'prioridad' => 2],
            ['rol_id' => $rol3->getId(), 'prioridad' => 3],
        ], array_map(static fn (array $r): array => ['rol_id' => (int) $r['rol_id'], 'prioridad' => (int) $r['prioridad']], $rolesGuardados));
    }

    public function testCrearPersonalSinTiendasNiRoles(): void
    {
        $client = static::createClient();
        $usuario = $this->crearUsuario($this->uniqueEmail('pers_min'));

        $this->login($client, $usuario->getEmail());

        $crawler = $client->request('GET', '/personal/nuevo');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Guardar')->form();
        $form['personal[nombre]'] = 'Carlos Nuevo';
        $client->submit($form);

        self::assertResponseRedirects('/personal');
        $client->followRedirect();
        self::assertStringContainsString('Carlos Nuevo', (string) $client->getResponse()->getContent());
        self::assertStringContainsString('48 h/sem', (string) $client->getResponse()->getContent());
    }

    public function testRechazaRolDeOtroUsuario(): void
    {
        $client = static::createClient();
        $usuario = $this->crearUsuario($this->uniqueEmail('pers_prop'));
        $rolAjeno = $this->crearRol($this->crearUsuario($this->uniqueEmail('pers_otro_rol')), 'Rol ajeno');

        $this->login($client, $usuario->getEmail());

        $crawler = $client->request('GET', '/personal/nuevo');
        $form = $crawler->selectButton('Guardar')->form();
        $form['personal[nombre]'] = 'Intento Ajeno';
        $form['personal[rolesOrdenados]'] = (string) $rolAjeno->getId();
        $client->submit($form);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Uno de los roles seleccionados no es válido.', (string) $client->getResponse()->getContent());
    }

    public function testEditarReordenaRolesEnSitioYConservaRolDesactivado(): void
    {
        $client = static::createClient();
        $usuario = $this->crearUsuario($this->uniqueEmail('pers_edit'));
        $rolA = $this->crearRol($usuario, 'Cajero', true);
        $rolB = $this->crearRol($usuario, 'Encargado', true);
        $rolInactivo = $this->crearRol($usuario, 'Rol legado', false);
        $persona = $this->crearPersonal($usuario, 'Sofía Ruiz', true, [], [$rolA, $rolB, $rolInactivo]);
        $connection = static::getContainer()->get(EntityManagerInterface::class)->getConnection();

        $this->login($client, $usuario->getEmail());

        $crawler = $client->request('GET', '/personal/'.$persona->getId().'/editar');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Rol legado', (string) $client->getResponse()->getContent());

        // Invierte rolB (#1) y rolA (#2), y quita rolInactivo en un solo flush() sin colisión de índice único.
        $form = $crawler->selectButton('Guardar')->form();
        $form['personal[nombre]'] = 'Sofía Ruiz Actualizada';
        $form['personal[rolesOrdenados]'] = $rolB->getId().','.$rolA->getId();
        $client->submit($form);

        self::assertResponseRedirects('/personal');

        $rolesGuardados = $connection->fetchAllAssociative('SELECT rol_id, prioridad FROM personal_rol WHERE personal_id = ? ORDER BY prioridad ASC', [$persona->getId()]);
        self::assertSame([
            ['rol_id' => $rolB->getId(), 'prioridad' => 1],
            ['rol_id' => $rolA->getId(), 'prioridad' => 2],
        ], array_map(static fn (array $r): array => ['rol_id' => (int) $r['rol_id'], 'prioridad' => (int) $r['prioridad']], $rolesGuardados));
    }

    public function testEditarPersonalAjenoDa404(): void
    {
        $client = static::createClient();
        $persona = $this->crearPersonal($this->crearUsuario($this->uniqueEmail('pers_dueno')), 'Ajena');

        $this->login($client, $this->crearUsuario($this->uniqueEmail('pers_intruso'))->getEmail());
        $client->request('GET', '/personal/'.$persona->getId().'/editar');

        self::assertResponseStatusCodeSame(404);
    }

    public function testAlternarDesactivaYReactiva(): void
    {
        $client = static::createClient();
        $usuario = $this->crearUsuario($this->uniqueEmail('pers_alt'));
        $persona = $this->crearPersonal($usuario, 'Mario Torres');
        $connection = static::getContainer()->get(EntityManagerInterface::class)->getConnection();

        $this->login($client, $usuario->getEmail());

        $client->request('POST', '/personal/'.$persona->getId().'/alternar', ['_token' => $this->tokenDeListado($client)]);
        self::assertResponseRedirects('/personal');
        self::assertEquals(0, $connection->fetchOne('SELECT activo FROM personal WHERE id = ?', [$persona->getId()]));

        $client->request('POST', '/personal/'.$persona->getId().'/alternar', ['_token' => $this->tokenDeListado($client)]);
        self::assertResponseRedirects('/personal');
        self::assertEquals(1, $connection->fetchOne('SELECT activo FROM personal WHERE id = ?', [$persona->getId()]));
    }

    private function tokenDeListado(KernelBrowser $client): string
    {
        $client->request('GET', '/personal');
        $html = (string) $client->getResponse()->getContent();
        preg_match('/name="_token" value="([^"]+)"/', $html, $matches);

        return $matches[1];
    }
}
