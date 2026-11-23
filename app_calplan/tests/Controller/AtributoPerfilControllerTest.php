<?php

namespace App\Tests\Controller;

use App\Entity\AtributoPerfil;
use App\Entity\Personal;
use App\Entity\User;
use App\Entity\ValorPerfil;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AtributoPerfilControllerTest extends WebTestCase
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
     * @param list<string>|null $opciones
     */
    private function crearAtributo(
        User $usuario,
        string $nombre,
        string $tipo = AtributoPerfil::TIPO_ESCALA,
        ?array $opciones = null,
        int $orden = 1,
        bool $activo = true,
    ): AtributoPerfil {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $atributo = new AtributoPerfil();
        $atributo->setUsuario($usuario)
            ->setNombre($nombre)
            ->setTipo($tipo)
            ->setOpciones($opciones)
            ->setOrden($orden)
            ->setActivo($activo);
        $entityManager->persist($atributo);
        $entityManager->flush();

        return $atributo;
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
        $client->request('GET', '/perfil/atributos');

        self::assertResponseRedirects('/login');
    }

    public function testListaSoloPropiosYCargaSugeridosDeFormaIdempotente(): void
    {
        $client = static::createClient();
        $usuario = $this->crearUsuario($this->uniqueEmail('attr_list'));
        $otro = $this->crearUsuario($this->uniqueEmail('attr_otro'));
        $this->crearAtributo($otro, 'Atributo Ajeno');
        $connection = static::getContainer()->get(EntityManagerInterface::class)->getConnection();

        $this->login($client, $usuario->getEmail());

        $crawler = $client->request('GET', '/perfil/atributos');
        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('Atributo Ajeno', (string) $client->getResponse()->getContent());

        // Carga inicial de los 5 sugeridos descriptivos.
        $formSugeridos = $crawler->selectButton('Cargar sugeridos')->form();
        $client->submit($formSugeridos);
        self::assertResponseRedirects('/perfil/atributos');
        $crawler = $client->followRedirect();
        self::assertResponseIsSuccessful();

        $html = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('Velocidad', $html);
        self::assertStringContainsString('Fuerza', $html);
        self::assertStringContainsString('Puntualidad', $html);
        self::assertStringContainsString('Confiabilidad', $html);
        self::assertStringContainsString('Sexo', $html);
        self::assertSame(5, (int) $connection->fetchOne('SELECT COUNT(*) FROM atributo_perfil WHERE usuario_id = ?', [$usuario->getId()]));

        // Ejecutar por segunda vez es idempotente y no duplica registros.
        $client->submit($crawler->selectButton('Cargar sugeridos')->form());
        self::assertResponseRedirects('/perfil/atributos');
        self::assertSame(5, (int) $connection->fetchOne('SELECT COUNT(*) FROM atributo_perfil WHERE usuario_id = ?', [$usuario->getId()]));
    }

    public function testCrearValidandoListaYUnicidadPorUsuario(): void
    {
        $client = static::createClient();
        $usuario = $this->crearUsuario($this->uniqueEmail('attr_crea'));
        $connection = static::getContainer()->get(EntityManagerInterface::class)->getConnection();

        $this->login($client, $usuario->getEmail());

        // Tipo lista sin opciones muestra error.
        $crawler = $client->request('GET', '/perfil/atributos/nuevo');
        $form = $crawler->selectButton('Guardar')->form();
        $form['atributo_perfil[nombre]'] = 'Turno preferido';
        $form['atributo_perfil[tipo]'] = AtributoPerfil::TIPO_LISTA;
        $form['atributo_perfil[opcionesTexto]'] = '   ';
        $form['atributo_perfil[orden]'] = '1';
        $client->submit($form);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Agrega al menos una opción para el tipo lista.', (string) $client->getResponse()->getContent());

        // Alta válida con opciones deduplicadas.
        $form = $crawler->selectButton('Guardar')->form();
        $form['atributo_perfil[nombre]'] = 'Turno preferido';
        $form['atributo_perfil[tipo]'] = AtributoPerfil::TIPO_LISTA;
        $form['atributo_perfil[opcionesTexto]'] = "Matutino\nVespertino, Matutino";
        $form['atributo_perfil[orden]'] = '2';
        $client->submit($form);

        self::assertResponseRedirects('/perfil/atributos');
        $opcionesJson = $connection->fetchOne('SELECT opciones FROM atributo_perfil WHERE usuario_id = ? AND nombre = ?', [$usuario->getId(), 'Turno preferido']);
        self::assertSame(['Matutino', 'Vespertino'], json_decode((string) $opcionesJson, true));

        // Duplicado para el mismo usuario es rechazado.
        $crawlerDup = $client->request('GET', '/perfil/atributos/nuevo');
        $formDup = $crawlerDup->selectButton('Guardar')->form();
        $formDup['atributo_perfil[nombre]'] = 'Turno preferido';
        $formDup['atributo_perfil[tipo]'] = AtributoPerfil::TIPO_TEXTO;
        $formDup['atributo_perfil[orden]'] = '3';
        $client->submit($formDup);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Ya tienes un atributo de perfil con ese nombre.', (string) $client->getResponse()->getContent());
    }

    public function testBloqueaCambioDeTipoCuandoTieneValoresCapturados(): void
    {
        $client = static::createClient();
        $usuario = $this->crearUsuario($this->uniqueEmail('attr_lock'));
        $atributo = $this->crearAtributo($usuario, 'Puntualidad', AtributoPerfil::TIPO_ESCALA);

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $persona = new Personal();
        $persona->setUsuario($usuario)->setNombre('Laura Test');
        $valor = new ValorPerfil($persona, $atributo, '5');
        $entityManager->persist($persona);
        $entityManager->persist($valor);
        $entityManager->flush();

        $connection = $entityManager->getConnection();
        $this->login($client, $usuario->getEmail());

        $crawler = $client->request('GET', '/perfil/atributos/'.$atributo->getId().'/editar');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('El tipo no puede cambiarse porque ya tiene valores capturados', (string) $client->getResponse()->getContent());

        // Intento manipulado por POST de cambiar el tipo a texto debe conservar escala.
        $csrf = $crawler->filter('input[name="atributo_perfil[_token]"]')->attr('value');
        $client->request('POST', '/perfil/atributos/'.$atributo->getId().'/editar', [
            'atributo_perfil' => [
                'nombre' => 'Puntualidad general',
                'tipo' => AtributoPerfil::TIPO_TEXTO,
                'opcionesTexto' => '',
                'orden' => '1',
                '_token' => $csrf,
            ],
        ]);

        self::assertResponseRedirects('/perfil/atributos');
        self::assertSame(AtributoPerfil::TIPO_ESCALA, $connection->fetchOne('SELECT tipo FROM atributo_perfil WHERE id = ?', [$atributo->getId()]));
        self::assertSame('Puntualidad general', $connection->fetchOne('SELECT nombre FROM atributo_perfil WHERE id = ?', [$atributo->getId()]));
    }

    public function testAlternarYProteccionAjeno404(): void
    {
        $client = static::createClient();
        $dueno = $this->crearUsuario($this->uniqueEmail('attr_dueno'));
        $intruso = $this->crearUsuario($this->uniqueEmail('attr_intruso'));
        $atributo = $this->crearAtributo($dueno, 'Fuerza', AtributoPerfil::TIPO_ESCALA);
        $connection = static::getContainer()->get(EntityManagerInterface::class)->getConnection();

        $this->login($client, $intruso->getEmail());
        $client->request('GET', '/perfil/atributos/'.$atributo->getId().'/editar');
        self::assertResponseStatusCodeSame(404);

        $this->login($client, $dueno->getEmail());
        $crawler = $client->request('GET', '/perfil/atributos');
        $client->submit($crawler->selectButton('Desactivar')->form());
        self::assertResponseRedirects('/perfil/atributos');
        self::assertEquals(0, $connection->fetchOne('SELECT activo FROM atributo_perfil WHERE id = ?', [$atributo->getId()]));

        $crawler = $client->request('GET', '/perfil/atributos');
        $client->submit($crawler->selectButton('Reactivar')->form());
        self::assertResponseRedirects('/perfil/atributos');
        self::assertEquals(1, $connection->fetchOne('SELECT activo FROM atributo_perfil WHERE id = ?', [$atributo->getId()]));
    }
}
