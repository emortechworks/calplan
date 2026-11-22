<?php

namespace App\Tests\Controller;

use App\Entity\Rol;
use App\Entity\Tienda;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class TiendaControllerTest extends WebTestCase
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

    private function crearTienda(User $usuario, string $nombre, ?string $codigo = null): Tienda
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $tienda = new Tienda();
        $tienda->setUsuario($usuario);
        $tienda->setNombre($nombre);
        $tienda->setCodigo($codigo);
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
        $client->request('GET', '/tiendas');

        self::assertResponseRedirects('/login');
    }

    public function testListaPropiasActivasYDesactivadasYNoAjenas(): void
    {
        $client = static::createClient();

        $usuario = $this->crearUsuario($this->uniqueEmail('tienda_list'));
        $this->crearTienda($usuario, 'Tienda activa');
        $tiendaInactiva = $this->crearTienda($usuario, 'Tienda desactivada');
        $tiendaInactiva->setActiva(false);
        static::getContainer()->get(EntityManagerInterface::class)->flush();

        $this->crearTienda($this->crearUsuario($this->uniqueEmail('tienda_ajena')), 'Tienda de otro');

        $this->login($client, $usuario->getEmail());
        $client->request('GET', '/tiendas');

        self::assertResponseIsSuccessful();
        $html = $client->getResponse()->getContent();
        self::assertStringContainsString('Tienda activa', $html);
        self::assertStringContainsString('Tienda desactivada', $html);
        self::assertStringContainsString('desactivada', $html);
        self::assertStringNotContainsString('Tienda de otro', $html);
    }

    public function testCrearTienda(): void
    {
        $client = static::createClient();
        $this->login($client, $this->crearUsuario($this->uniqueEmail('tienda_crea'))->getEmail());

        $crawler = $client->request('GET', '/tiendas/nueva');
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton('Guardar')->form();
        $form['tienda[nombre]'] = 'Tienda nueva';
        $form['tienda[codigo]'] = 'TN-1';
        $client->submit($form);

        self::assertResponseRedirects('/tiendas');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Tienda nueva', $client->getResponse()->getContent());
    }

    public function testNombreDuplicadoMuestraError(): void
    {
        $client = static::createClient();
        $usuario = $this->crearUsuario($this->uniqueEmail('tienda_dup'));
        $this->crearTienda($usuario, 'Centro');

        $this->login($client, $usuario->getEmail());

        $crawler = $client->request('GET', '/tiendas/nueva');
        $form = $crawler->selectButton('Guardar')->form();
        $form['tienda[nombre]'] = 'Centro';
        $client->submit($form);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Ya tienes una tienda con ese nombre.', $client->getResponse()->getContent());
    }

    public function testEditarPropiaTienda(): void
    {
        $client = static::createClient();
        $usuario = $this->crearUsuario($this->uniqueEmail('tienda_editar'));
        $tienda = $this->crearTienda($usuario, 'Centro');

        $this->login($client, $usuario->getEmail());

        $crawler = $client->request('GET', '/tiendas/'.$tienda->getId().'/editar');
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton('Guardar')->form();
        $form['tienda[nombre]'] = 'Centro Editado';
        $client->submit($form);

        self::assertResponseRedirects('/tiendas');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Centro Editado', $client->getResponse()->getContent());
    }

    public function testEditarTiendaAjenaDa404(): void
    {
        $client = static::createClient();
        $tienda = $this->crearTienda($this->crearUsuario($this->uniqueEmail('tienda_ajenas')), 'Ajena');

        $this->login($client, $this->crearUsuario($this->uniqueEmail('tienda_otro_editar'))->getEmail());
        $client->request('GET', '/tiendas/'.$tienda->getId().'/editar');

        self::assertResponseStatusCodeSame(404);
    }

    public function testAlternarDesactivaYReactiva(): void
    {
        $client = static::createClient();
        $usuario = $this->crearUsuario($this->uniqueEmail('tienda_alternar'));
        $tienda = $this->crearTienda($usuario, 'Alterna');
        $connection = static::getContainer()->get(EntityManagerInterface::class)->getConnection();

        $this->login($client, $usuario->getEmail());

        $client->request('POST', '/tiendas/'.$tienda->getId().'/alternar', ['_token' => $this->tokenDeListado($client)]);
        self::assertResponseRedirects('/tiendas');
        self::assertEquals(0, $connection->fetchOne('SELECT activa FROM tienda WHERE id = ?', [$tienda->getId()]));

        $client->request('POST', '/tiendas/'.$tienda->getId().'/alternar', ['_token' => $this->tokenDeListado($client)]);
        self::assertResponseRedirects('/tiendas');
        self::assertEquals(1, $connection->fetchOne('SELECT activa FROM tienda WHERE id = ?', [$tienda->getId()]));
    }

    public function testAsignarYEditarRolesEnTienda(): void
    {
        $client = static::createClient();
        $usuario = $this->crearUsuario($this->uniqueEmail('tienda_roles'));
        $rolA = $this->crearRol($usuario, 'Cajero');
        $rolB = $this->crearRol($usuario, 'Panadero');
        $connection = static::getContainer()->get(EntityManagerInterface::class)->getConnection();

        $this->login($client, $usuario->getEmail());

        $crawler = $client->request('GET', '/tiendas/nueva');
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton('Guardar')->form();
        $form['tienda[nombre]'] = 'Sucursal Con Roles';
        $form['tienda[roles]'][0]->tick();
        $form['tienda[roles]'][1]->tick();
        $client->submit($form);

        self::assertResponseRedirects('/tiendas');
        $client->followRedirect();
        $html = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('Sucursal Con Roles', $html);
        self::assertStringContainsString('Cajero', $html);
        self::assertStringContainsString('Panadero', $html);

        $tiendaId = (int) $connection->fetchOne('SELECT id FROM tienda WHERE usuario_id = ? AND nombre = ?', [$usuario->getId(), 'Sucursal Con Roles']);
        $rolesGuardados = $connection->fetchFirstColumn('SELECT rol_id FROM tienda_rol WHERE tienda_id = ?', [$tiendaId]);
        self::assertEqualsCanonicalizing([$rolA->getId(), $rolB->getId()], array_map('intval', $rolesGuardados));
    }

    /**
     * Extrae el token CSRF del formulario de alternar del listado
     * (visita GET /tiendas tras el login, luego lee el `_token` oculto).
     */
    private function tokenDeListado(KernelBrowser $client): string
    {
        $client->request('GET', '/tiendas');
        $html = $client->getResponse()->getContent();
        preg_match('/name="_token" value="([^"]+)"/', $html, $matches);

        return $matches[1];
    }
}
