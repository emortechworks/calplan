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

    /**
     * @param list<Rol> $roles
     */
    private function crearTienda(User $usuario, string $nombre, bool $activa = true, array $roles = []): Tienda
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $tienda = new Tienda();
        $tienda->setUsuario($usuario);
        $tienda->setNombre($nombre);
        $tienda->setActiva($activa);
        foreach ($roles as $rol) {
            $tienda->addRol($rol);
        }
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
     * @param list<Tienda>                                  $tiendas
     * @param list<array{tienda: Tienda, roles: list<Rol>}> $rolesPorTienda
     */
    private function crearPersonal(User $usuario, string $nombre, bool $activo = true, array $tiendas = [], array $rolesPorTienda = []): Personal
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $persona = new Personal();
        $persona->setUsuario($usuario);
        $persona->setNombre($nombre);
        $persona->setActivo($activo);
        foreach ($tiendas as $tienda) {
            $persona->addTienda($tienda);
        }
        $persona->reemplazarRolesPorTienda($rolesPorTienda);
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
        $rolGerente = $this->crearRol($usuario, 'Gerente titular');
        $rolCaja = $this->crearRol($usuario, 'Encargado de caja');
        $tiendaCentro = $this->crearTienda($usuario, 'Sucursal Centro', true, [$rolGerente, $rolCaja]);
        $tiendaNorte = $this->crearTienda($usuario, 'Sucursal Norte', true, [$rolCaja]);

        $this->crearPersonal($usuario, 'Ana López', true, [$tiendaCentro], [
            ['tienda' => $tiendaCentro, 'roles' => [$rolGerente, $rolCaja]],
        ]);
        $this->crearPersonal($usuario, 'Luis Pérez', false, [$tiendaNorte], [
            ['tienda' => $tiendaNorte, 'roles' => [$rolCaja]],
        ]);
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

    public function testCrearPersonalConPrioridadesIndependientesPorTienda(): void
    {
        $client = static::createClient();
        $usuario = $this->crearUsuario($this->uniqueEmail('pers_crea'));
        $rolGerente = $this->crearRol($usuario, 'Gerente titular');
        $rolPanadero = $this->crearRol($usuario, 'Panadero');
        $rolCajero = $this->crearRol($usuario, 'Cajero');
        $tiendaCentro = $this->crearTienda($usuario, 'Centro', true, [$rolGerente, $rolPanadero]);
        $tiendaNorte = $this->crearTienda($usuario, 'Norte', true, [$rolGerente, $rolCajero]);
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
        $form['personal[rolesPorTienda]'] = json_encode([
            $tiendaCentro->getId() => [$rolPanadero->getId(), $rolGerente->getId()],
            $tiendaNorte->getId() => [$rolGerente->getId(), $rolCajero->getId()],
        ]);
        $client->submit($form);

        self::assertResponseRedirects('/personal');
        $client->followRedirect();
        self::assertResponseIsSuccessful();

        $html = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('María García', $html);
        self::assertStringContainsString('40 h/sem', $html);

        $personaId = (int) $connection->fetchOne('SELECT id FROM personal WHERE usuario_id = ? AND nombre = ?', [$usuario->getId(), 'María García']);
        self::assertGreaterThan(0, $personaId);

        $tiendasGuardadas = $connection->fetchFirstColumn('SELECT tienda_id FROM personal_tienda WHERE personal_id = ?', [$personaId]);
        self::assertEqualsCanonicalizing([$tiendaCentro->getId(), $tiendaNorte->getId()], array_map('intval', $tiendasGuardadas));

        $rolesGuardados = $connection->fetchAllAssociative(
            'SELECT tienda_id, rol_id, prioridad FROM personal_rol WHERE personal_id = ? ORDER BY tienda_id ASC, prioridad ASC',
            [$personaId],
        );
        self::assertSame([
            ['tienda_id' => $tiendaCentro->getId(), 'rol_id' => $rolPanadero->getId(), 'prioridad' => 1],
            ['tienda_id' => $tiendaCentro->getId(), 'rol_id' => $rolGerente->getId(), 'prioridad' => 2],
            ['tienda_id' => $tiendaNorte->getId(), 'rol_id' => $rolGerente->getId(), 'prioridad' => 1],
            ['tienda_id' => $tiendaNorte->getId(), 'rol_id' => $rolCajero->getId(), 'prioridad' => 2],
        ], array_map(
            static fn (array $r): array => ['tienda_id' => (int) $r['tienda_id'], 'rol_id' => (int) $r['rol_id'], 'prioridad' => (int) $r['prioridad']],
            $rolesGuardados,
        ));
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

    public function testRechazaRolQueNoPerteneceALaTiendaSeleccionada(): void
    {
        $client = static::createClient();
        $usuario = $this->crearUsuario($this->uniqueEmail('pers_cross'));
        $rolPanadero = $this->crearRol($usuario, 'Panadero');
        $rolCajero = $this->crearRol($usuario, 'Cajero');
        $tiendaCentro = $this->crearTienda($usuario, 'Centro', true, [$rolPanadero]);
        $this->crearTienda($usuario, 'Norte', true, [$rolCajero]);

        $this->login($client, $usuario->getEmail());

        $crawler = $client->request('GET', '/personal/nuevo');
        $form = $crawler->selectButton('Guardar')->form();
        $form['personal[nombre]'] = 'Intento Rol Cruzado';
        $form['personal[tiendas]'][0]->tick(); // Solo Centro (que no tiene Cajero)
        $form['personal[rolesPorTienda]'] = json_encode([
            $tiendaCentro->getId() => [$rolCajero->getId()],
        ]);
        $client->submit($form);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Uno de los roles seleccionados no pertenece a esa tienda.', (string) $client->getResponse()->getContent());
    }

    public function testRechazaRolDeTiendaNoSeleccionada(): void
    {
        $client = static::createClient();
        $usuario = $this->crearUsuario($this->uniqueEmail('pers_notienda'));
        $rolCajero = $this->crearRol($usuario, 'Cajero');
        $tiendaCentro = $this->crearTienda($usuario, 'Centro', true, [$rolCajero]);

        $this->login($client, $usuario->getEmail());

        $crawler = $client->request('GET', '/personal/nuevo');
        $form = $crawler->selectButton('Guardar')->form();
        $form['personal[nombre]'] = 'Sin Tienda Marcada';
        $form['personal[rolesPorTienda]'] = json_encode([
            $tiendaCentro->getId() => [$rolCajero->getId()],
        ]);
        $client->submit($form);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('No puedes asignar roles de una tienda que no está seleccionada.', (string) $client->getResponse()->getContent());
    }

    public function testEditarReordenaRolesEnSitioYConservaRolDesactivado(): void
    {
        $client = static::createClient();
        $usuario = $this->crearUsuario($this->uniqueEmail('pers_edit'));
        $rolA = $this->crearRol($usuario, 'Cajero', true);
        $rolB = $this->crearRol($usuario, 'Encargado', true);
        $rolInactivo = $this->crearRol($usuario, 'Rol legado', false);
        $tienda = $this->crearTienda($usuario, 'Centro', true, [$rolA, $rolB, $rolInactivo]);
        $persona = $this->crearPersonal($usuario, 'Sofía Ruiz', true, [$tienda], [
            ['tienda' => $tienda, 'roles' => [$rolA, $rolB, $rolInactivo]],
        ]);
        $connection = static::getContainer()->get(EntityManagerInterface::class)->getConnection();

        $this->login($client, $usuario->getEmail());

        $crawler = $client->request('GET', '/personal/'.$persona->getId().'/editar');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Rol legado', (string) $client->getResponse()->getContent());

        // Invierte rolB (#1) y rolA (#2), y quita rolInactivo en un solo flush() sin colisión de índice único.
        $form = $crawler->selectButton('Guardar')->form();
        $form['personal[nombre]'] = 'Sofía Ruiz Actualizada';
        $form['personal[rolesPorTienda]'] = json_encode([
            $tienda->getId() => [$rolB->getId(), $rolA->getId()],
        ]);
        $client->submit($form);

        self::assertResponseRedirects('/personal');

        $rolesGuardados = $connection->fetchAllAssociative(
            'SELECT tienda_id, rol_id, prioridad FROM personal_rol WHERE personal_id = ? ORDER BY prioridad ASC',
            [$persona->getId()],
        );
        self::assertSame([
            ['tienda_id' => $tienda->getId(), 'rol_id' => $rolB->getId(), 'prioridad' => 1],
            ['tienda_id' => $tienda->getId(), 'rol_id' => $rolA->getId(), 'prioridad' => 2],
        ], array_map(
            static fn (array $r): array => ['tienda_id' => (int) $r['tienda_id'], 'rol_id' => (int) $r['rol_id'], 'prioridad' => (int) $r['prioridad']],
            $rolesGuardados,
        ));
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
