<?php

namespace App\Tests\Controller;

use App\Entity\Rol;
use App\Entity\Sucursal;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class SucursalControllerTest extends WebTestCase
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

    private function crearSucursal(User $usuario, string $nombre, ?string $codigo = null): Sucursal
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $sucursal = new Sucursal();
        $sucursal->setUsuario($usuario);
        $sucursal->setNombre($nombre);
        $sucursal->setCodigo($codigo);
        $entityManager->persist($sucursal);
        $entityManager->flush();

        return $sucursal;
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
        $client->request('GET', '/sucursales');

        self::assertResponseRedirects('/login');
    }

    public function testListaPropiasActivasYDesactivadasYNoAjenas(): void
    {
        $client = static::createClient();

        $usuario = $this->crearUsuario($this->uniqueEmail('suc_list'));
        $this->crearSucursal($usuario, 'Sucursal activa');
        $sucursalInactiva = $this->crearSucursal($usuario, 'Sucursal desactivada');
        $sucursalInactiva->setActiva(false);
        static::getContainer()->get(EntityManagerInterface::class)->flush();

        $this->crearSucursal($this->crearUsuario($this->uniqueEmail('suc_ajena')), 'Sucursal de otro');

        $this->login($client, $usuario->getEmail());
        $client->request('GET', '/sucursales');

        self::assertResponseIsSuccessful();
        $html = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('Sucursal activa', $html);
        self::assertStringContainsString('Sucursal desactivada', $html);
        self::assertStringContainsString('desactivada', $html);
        self::assertStringNotContainsString('Sucursal de otro', $html);
    }

    public function testCrearSucursal(): void
    {
        $client = static::createClient();
        $this->login($client, $this->crearUsuario($this->uniqueEmail('suc_crea'))->getEmail());

        $crawler = $client->request('GET', '/sucursales/nueva');
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton('Guardar')->form();
        $form['sucursal[nombre]'] = 'Sucursal nueva';
        $form['sucursal[codigo]'] = 'SN-1';
        $client->submit($form);

        self::assertResponseRedirects('/sucursales');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Sucursal nueva', (string) $client->getResponse()->getContent());
    }

    public function testNombreDuplicadoMuestraError(): void
    {
        $client = static::createClient();
        $usuario = $this->crearUsuario($this->uniqueEmail('suc_dup'));
        $this->crearSucursal($usuario, 'Centro');

        $this->login($client, $usuario->getEmail());

        $crawler = $client->request('GET', '/sucursales/nueva');
        $form = $crawler->selectButton('Guardar')->form();
        $form['sucursal[nombre]'] = 'Centro';
        $client->submit($form);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Ya tienes una sucursal con ese nombre.', (string) $client->getResponse()->getContent());
    }

    public function testEditarPropiaSucursal(): void
    {
        $client = static::createClient();
        $usuario = $this->crearUsuario($this->uniqueEmail('suc_editar'));
        $sucursal = $this->crearSucursal($usuario, 'Centro');

        $this->login($client, $usuario->getEmail());

        $crawler = $client->request('GET', '/sucursales/'.$sucursal->getId().'/editar');
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton('Guardar')->form();
        $form['sucursal[nombre]'] = 'Centro Editado';
        $client->submit($form);

        self::assertResponseRedirects('/sucursales');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Centro Editado', (string) $client->getResponse()->getContent());
    }

    public function testEditarSucursalAjenaDa404(): void
    {
        $client = static::createClient();
        $sucursal = $this->crearSucursal($this->crearUsuario($this->uniqueEmail('suc_ajenas')), 'Ajena');

        $this->login($client, $this->crearUsuario($this->uniqueEmail('suc_otro_editar'))->getEmail());
        $client->request('GET', '/sucursales/'.$sucursal->getId().'/editar');

        self::assertResponseStatusCodeSame(404);
    }

    public function testAlternarDesactivaYReactiva(): void
    {
        $client = static::createClient();
        $usuario = $this->crearUsuario($this->uniqueEmail('suc_alternar'));
        $sucursal = $this->crearSucursal($usuario, 'Alterna');
        $connection = static::getContainer()->get(EntityManagerInterface::class)->getConnection();

        $this->login($client, $usuario->getEmail());

        $client->request('POST', '/sucursales/'.$sucursal->getId().'/alternar', ['_token' => $this->tokenDeListado($client)]);
        self::assertResponseRedirects('/sucursales');
        self::assertEquals(0, $connection->fetchOne('SELECT activa FROM sucursal WHERE id = ?', [$sucursal->getId()]));

        $client->request('POST', '/sucursales/'.$sucursal->getId().'/alternar', ['_token' => $this->tokenDeListado($client)]);
        self::assertResponseRedirects('/sucursales');
        self::assertEquals(1, $connection->fetchOne('SELECT activa FROM sucursal WHERE id = ?', [$sucursal->getId()]));
    }

    public function testAsignarYEditarRolesEnSucursal(): void
    {
        $client = static::createClient();
        $usuario = $this->crearUsuario($this->uniqueEmail('suc_roles'));
        $rolA = $this->crearRol($usuario, 'Cajero');
        $rolB = $this->crearRol($usuario, 'Panadero');
        $connection = static::getContainer()->get(EntityManagerInterface::class)->getConnection();

        $this->login($client, $usuario->getEmail());

        $crawler = $client->request('GET', '/sucursales/nueva');
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton('Guardar')->form();
        $form['sucursal[nombre]'] = 'Sucursal Con Roles';
        $form['sucursal[roles]'][0]->tick();
        $form['sucursal[roles]'][1]->tick();
        $client->submit($form);

        self::assertResponseRedirects('/sucursales');
        $client->followRedirect();
        $html = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('Sucursal Con Roles', $html);
        self::assertStringContainsString('Cajero', $html);
        self::assertStringContainsString('Panadero', $html);

        $sucursalId = (int) $connection->fetchOne('SELECT id FROM sucursal WHERE usuario_id = ? AND nombre = ?', [$usuario->getId(), 'Sucursal Con Roles']);
        $rolesGuardados = $connection->fetchFirstColumn('SELECT rol_id FROM sucursal_rol WHERE sucursal_id = ?', [$sucursalId]);
        self::assertEqualsCanonicalizing([$rolA->getId(), $rolB->getId()], array_map('intval', $rolesGuardados));
    }

    /**
     * Extrae el token CSRF del formulario de alternar del listado
     * (visita GET /sucursales tras el login, luego lee el `_token` oculto).
     */
    private function tokenDeListado(KernelBrowser $client): string
    {
        $client->request('GET', '/sucursales');
        $html = (string) $client->getResponse()->getContent();
        preg_match('/name="_token" value="([^"]+)"/', $html, $matches);

        return $matches[1];
    }
}
