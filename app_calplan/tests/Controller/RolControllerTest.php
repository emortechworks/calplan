<?php

namespace App\Tests\Controller;

use App\Entity\Rol;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RolControllerTest extends WebTestCase
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
        $client->request('GET', '/roles');

        self::assertResponseRedirects('/login');
    }

    public function testListaPropiosActivosYDesactivadosYNoAjenos(): void
    {
        $client = static::createClient();

        $usuario = $this->crearUsuario($this->uniqueEmail('rol_list'));
        $this->crearRol($usuario, 'Encargado de área', true);
        $this->crearRol($usuario, 'Suplente temporal', false);
        $this->crearRol($this->crearUsuario($this->uniqueEmail('rol_ajeno')), 'Rol de otro');

        $this->login($client, $usuario->getEmail());
        $client->request('GET', '/roles');

        self::assertResponseIsSuccessful();
        $html = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('Roles', $html);
        self::assertStringContainsString('Encargado de área', $html);
        self::assertStringContainsString('Suplente temporal', $html);
        self::assertStringContainsString('desactivado', $html);
        self::assertStringNotContainsString('Rol de otro', $html);
    }

    public function testCrearRol(): void
    {
        $client = static::createClient();
        $this->login($client, $this->crearUsuario($this->uniqueEmail('rol_crea'))->getEmail());

        $crawler = $client->request('GET', '/roles/nuevo');
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton('Guardar')->form();
        $form['rol[nombre]'] = 'Gerente titular';
        $client->submit($form);

        self::assertResponseRedirects('/roles');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Gerente titular', (string) $client->getResponse()->getContent());
    }

    public function testNombreDuplicadoMuestraError(): void
    {
        $client = static::createClient();
        $usuario = $this->crearUsuario($this->uniqueEmail('rol_dup'));
        $this->crearRol($usuario, 'Gerente titular');

        $this->login($client, $usuario->getEmail());

        $crawler = $client->request('GET', '/roles/nuevo');
        $form = $crawler->selectButton('Guardar')->form();
        $form['rol[nombre]'] = 'Gerente titular';
        $client->submit($form);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Ya tienes un rol con ese nombre.', (string) $client->getResponse()->getContent());
    }

    public function testEditarPropioRol(): void
    {
        $client = static::createClient();
        $usuario = $this->crearUsuario($this->uniqueEmail('rol_editar'));
        $rol = $this->crearRol($usuario, 'Auxiliar');

        $this->login($client, $usuario->getEmail());

        $crawler = $client->request('GET', '/roles/'.$rol->getId().'/editar');
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton('Guardar')->form();
        $form['rol[nombre]'] = 'Encargado de caja';
        $client->submit($form);

        self::assertResponseRedirects('/roles');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Encargado de caja', (string) $client->getResponse()->getContent());
    }

    public function testEditarRolAjenoDa404(): void
    {
        $client = static::createClient();
        $rol = $this->crearRol($this->crearUsuario($this->uniqueEmail('rol_ajenos')), 'Ajeno');

        $this->login($client, $this->crearUsuario($this->uniqueEmail('rol_otro_editar'))->getEmail());
        $client->request('GET', '/roles/'.$rol->getId().'/editar');

        self::assertResponseStatusCodeSame(404);
    }

    public function testAlternarDesactivaYReactiva(): void
    {
        $client = static::createClient();
        $usuario = $this->crearUsuario($this->uniqueEmail('rol_alternar'));
        $rol = $this->crearRol($usuario, 'Alterna');
        $connection = static::getContainer()->get(EntityManagerInterface::class)->getConnection();

        $this->login($client, $usuario->getEmail());

        $client->request('POST', '/roles/'.$rol->getId().'/alternar', ['_token' => $this->tokenDeListado($client)]);
        self::assertResponseRedirects('/roles');
        self::assertEquals(0, $connection->fetchOne('SELECT activo FROM rol WHERE id = ?', [$rol->getId()]));

        $client->request('POST', '/roles/'.$rol->getId().'/alternar', ['_token' => $this->tokenDeListado($client)]);
        self::assertResponseRedirects('/roles');
        self::assertEquals(1, $connection->fetchOne('SELECT activo FROM rol WHERE id = ?', [$rol->getId()]));
    }

    /**
     * Extrae el token CSRF del formulario de alternar del listado
     * (visita GET /roles tras el login y lee el `_token` oculto).
     */
    private function tokenDeListado(KernelBrowser $client): string
    {
        $client->request('GET', '/roles');
        $html = (string) $client->getResponse()->getContent();
        preg_match('/name="_token" value="([^"]+)"/', $html, $matches);

        return $matches[1];
    }
}
