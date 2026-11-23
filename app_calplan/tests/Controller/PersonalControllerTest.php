<?php

namespace App\Tests\Controller;

use App\Entity\AtributoPerfil;
use App\Entity\Personal;
use App\Entity\Rol;
use App\Entity\Sucursal;
use App\Entity\User;
use App\Entity\ValorPerfil;
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
    private function crearSucursal(User $usuario, string $nombre, bool $activa = true, array $roles = []): Sucursal
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $sucursal = new Sucursal();
        $sucursal->setUsuario($usuario);
        $sucursal->setNombre($nombre);
        $sucursal->setActiva($activa);
        foreach ($roles as $rol) {
            $sucursal->addRol($rol);
        }
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

    /**
     * @param list<Sucursal>                                    $sucursales
     * @param list<array{sucursal: Sucursal, roles: list<Rol>}> $rolesPorSucursal
     */
    private function crearPersonal(User $usuario, string $nombre, bool $activo = true, array $sucursales = [], array $rolesPorSucursal = []): Personal
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $persona = new Personal();
        $persona->setUsuario($usuario);
        $persona->setNombre($nombre);
        $persona->setActivo($activo);
        foreach ($sucursales as $sucursal) {
            $persona->addSucursal($sucursal);
        }
        $persona->reemplazarRolesPorSucursal($rolesPorSucursal);
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

    public function testListaPropiosYFiltraPorSucursalSinMostrarAjenos(): void
    {
        $client = static::createClient();

        $usuario = $this->crearUsuario($this->uniqueEmail('pers_list'));
        $rolGerente = $this->crearRol($usuario, 'Gerente titular');
        $rolCaja = $this->crearRol($usuario, 'Encargado de caja');
        $sucursalCentro = $this->crearSucursal($usuario, 'Sucursal Centro', true, [$rolGerente, $rolCaja]);
        $sucursalNorte = $this->crearSucursal($usuario, 'Sucursal Norte', true, [$rolCaja]);

        $this->crearPersonal($usuario, 'Ana López', true, [$sucursalCentro], [
            ['sucursal' => $sucursalCentro, 'roles' => [$rolGerente, $rolCaja]],
        ]);
        $this->crearPersonal($usuario, 'Luis Pérez', false, [$sucursalNorte], [
            ['sucursal' => $sucursalNorte, 'roles' => [$rolCaja]],
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

        $client->request('GET', '/personal?sucursal='.$sucursalCentro->getId());
        self::assertResponseIsSuccessful();
        $htmlFiltrado = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('Ana López', $htmlFiltrado);
        self::assertStringNotContainsString('Luis Pérez', $htmlFiltrado);
    }

    public function testCrearPersonalConPrioridadesIndependientesPorSucursal(): void
    {
        $client = static::createClient();
        $usuario = $this->crearUsuario($this->uniqueEmail('pers_crea'));
        $rolGerente = $this->crearRol($usuario, 'Gerente titular');
        $rolPanadero = $this->crearRol($usuario, 'Panadero');
        $rolCajero = $this->crearRol($usuario, 'Cajero');
        $sucursalCentro = $this->crearSucursal($usuario, 'Centro', true, [$rolGerente, $rolPanadero]);
        $sucursalNorte = $this->crearSucursal($usuario, 'Norte', true, [$rolGerente, $rolCajero]);
        $connection = static::getContainer()->get(EntityManagerInterface::class)->getConnection();

        $this->login($client, $usuario->getEmail());

        $crawler = $client->request('GET', '/personal/nuevo');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Guardar')->form();
        $form['personal[nombre]'] = 'María García';
        $form['personal[horasSemanales]'] = '40';
        $form['personal[fechaNacimiento]'] = '1994-05-12';
        $form['personal[finContrato]'] = '2027-01-31';
        $form['personal[sucursales]'][0]->tick();
        $form['personal[sucursales]'][1]->tick();
        $form['personal[rolesPorSucursal]'] = json_encode([
            $sucursalCentro->getId() => [$rolPanadero->getId(), $rolGerente->getId()],
            $sucursalNorte->getId() => [$rolGerente->getId(), $rolCajero->getId()],
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

        $sucursalesGuardadas = $connection->fetchFirstColumn('SELECT sucursal_id FROM personal_sucursal WHERE personal_id = ?', [$personaId]);
        self::assertEqualsCanonicalizing([$sucursalCentro->getId(), $sucursalNorte->getId()], array_map('intval', $sucursalesGuardadas));

        $rolesGuardados = $connection->fetchAllAssociative(
            'SELECT sucursal_id, rol_id, prioridad FROM personal_rol WHERE personal_id = ? ORDER BY sucursal_id ASC, prioridad ASC',
            [$personaId],
        );
        self::assertSame([
            ['sucursal_id' => $sucursalCentro->getId(), 'rol_id' => $rolPanadero->getId(), 'prioridad' => 1],
            ['sucursal_id' => $sucursalCentro->getId(), 'rol_id' => $rolGerente->getId(), 'prioridad' => 2],
            ['sucursal_id' => $sucursalNorte->getId(), 'rol_id' => $rolGerente->getId(), 'prioridad' => 1],
            ['sucursal_id' => $sucursalNorte->getId(), 'rol_id' => $rolCajero->getId(), 'prioridad' => 2],
        ], array_map(
            static fn (array $r): array => ['sucursal_id' => (int) $r['sucursal_id'], 'rol_id' => (int) $r['rol_id'], 'prioridad' => (int) $r['prioridad']],
            $rolesGuardados,
        ));
    }

    public function testCrearPersonalSinSucursalesNiRoles(): void
    {
        $client = static::createClient();
        $usuario = $this->crearUsuario($this->uniqueEmail('pers_min'));
        $connection = static::getContainer()->get(EntityManagerInterface::class)->getConnection();

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

        $colorGuardado = $connection->fetchOne('SELECT color FROM personal WHERE usuario_id = ? AND nombre = ?', [$usuario->getId(), 'Carlos Nuevo']);
        self::assertSame('#ffffff', $colorGuardado);
    }

    public function testColorDePaletaYRechazoDeColorFueraDePaleta(): void
    {
        $client = static::createClient();
        $usuario = $this->crearUsuario($this->uniqueEmail('pers_color'));
        $connection = static::getContainer()->get(EntityManagerInterface::class)->getConnection();

        $this->login($client, $usuario->getEmail());

        $crawler = $client->request('GET', '/personal/nuevo');
        self::assertCount(20, $crawler->filter('select[name="personal[color]"] option'));
        $botonesColor = $crawler->filter('.color-opcion-btn');
        self::assertCount(20, $botonesColor);
        self::assertSame('', trim($botonesColor->first()->text()));

        $form = $crawler->selectButton('Guardar')->form();
        $form['personal[nombre]'] = 'Elena Color';
        $form['personal[color]']->select('#cfe2ff');
        $client->submit($form);

        self::assertResponseRedirects('/personal');
        $client->followRedirect();
        $html = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('background-color: #cfe2ff;', $html);

        $personaId = (int) $connection->fetchOne('SELECT id FROM personal WHERE usuario_id = ? AND nombre = ?', [$usuario->getId(), 'Elena Color']);
        self::assertSame('#cfe2ff', $connection->fetchOne('SELECT color FROM personal WHERE id = ?', [$personaId]));

        // Rechaza por POST un color oscuro fuera de la paleta de 20 colores claros.
        $crawlerEdit = $client->request('GET', '/personal/'.$personaId.'/editar');
        $csrf = $crawlerEdit->filter('input[name="personal[_token]"]')->attr('value');
        $client->request('POST', '/personal/'.$personaId.'/editar', [
            'personal' => [
                'nombre' => 'Elena Color',
                'horasSemanales' => '48',
                'color' => '#000000',
                'rolesPorSucursal' => '{}',
                '_token' => $csrf,
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame('#cfe2ff', $connection->fetchOne('SELECT color FROM personal WHERE id = ?', [$personaId]));
    }

    public function testRechazaRolQueNoPerteneceALaSucursalSeleccionada(): void
    {
        $client = static::createClient();
        $usuario = $this->crearUsuario($this->uniqueEmail('pers_cross'));
        $rolPanadero = $this->crearRol($usuario, 'Panadero');
        $rolCajero = $this->crearRol($usuario, 'Cajero');
        $sucursalCentro = $this->crearSucursal($usuario, 'Centro', true, [$rolPanadero]);
        $this->crearSucursal($usuario, 'Norte', true, [$rolCajero]);

        $this->login($client, $usuario->getEmail());

        $crawler = $client->request('GET', '/personal/nuevo');
        $form = $crawler->selectButton('Guardar')->form();
        $form['personal[nombre]'] = 'Intento Rol Cruzado';
        $form['personal[sucursales]'][0]->tick();
        $form['personal[rolesPorSucursal]'] = json_encode([
            $sucursalCentro->getId() => [$rolCajero->getId()],
        ]);
        $client->submit($form);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Uno de los roles seleccionados no pertenece a esa sucursal.', (string) $client->getResponse()->getContent());
    }

    public function testRechazaRolDeSucursalNoSeleccionada(): void
    {
        $client = static::createClient();
        $usuario = $this->crearUsuario($this->uniqueEmail('pers_nosuc'));
        $rolCajero = $this->crearRol($usuario, 'Cajero');
        $sucursalCentro = $this->crearSucursal($usuario, 'Centro', true, [$rolCajero]);

        $this->login($client, $usuario->getEmail());

        $crawler = $client->request('GET', '/personal/nuevo');
        $form = $crawler->selectButton('Guardar')->form();
        $form['personal[nombre]'] = 'Sin Sucursal Marcada';
        $form['personal[rolesPorSucursal]'] = json_encode([
            $sucursalCentro->getId() => [$rolCajero->getId()],
        ]);
        $client->submit($form);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('No puedes asignar roles de una sucursal que no está seleccionada.', (string) $client->getResponse()->getContent());
    }

    public function testEditarReordenaRolesEnSitioYConservaRolDesactivado(): void
    {
        $client = static::createClient();
        $usuario = $this->crearUsuario($this->uniqueEmail('pers_edit'));
        $rolA = $this->crearRol($usuario, 'Cajero', true);
        $rolB = $this->crearRol($usuario, 'Encargado', true);
        $rolInactivo = $this->crearRol($usuario, 'Rol legado', false);
        $sucursal = $this->crearSucursal($usuario, 'Centro', true, [$rolA, $rolB, $rolInactivo]);
        $persona = $this->crearPersonal($usuario, 'Sofía Ruiz', true, [$sucursal], [
            ['sucursal' => $sucursal, 'roles' => [$rolA, $rolB, $rolInactivo]],
        ]);
        $connection = static::getContainer()->get(EntityManagerInterface::class)->getConnection();

        $this->login($client, $usuario->getEmail());

        $crawler = $client->request('GET', '/personal/'.$persona->getId().'/editar');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Rol legado', (string) $client->getResponse()->getContent());

        // Invierte rolB (#1) y rolA (#2), y quita rolInactivo en un solo flush() sin colisión de índice único.
        $form = $crawler->selectButton('Guardar')->form();
        $form['personal[nombre]'] = 'Sofía Ruiz Actualizada';
        $form['personal[rolesPorSucursal]'] = json_encode([
            $sucursal->getId() => [$rolB->getId(), $rolA->getId()],
        ]);
        $client->submit($form);

        self::assertResponseRedirects('/personal');

        $rolesGuardados = $connection->fetchAllAssociative(
            'SELECT sucursal_id, rol_id, prioridad FROM personal_rol WHERE personal_id = ? ORDER BY prioridad ASC',
            [$persona->getId()],
        );
        self::assertSame([
            ['sucursal_id' => $sucursal->getId(), 'rol_id' => $rolB->getId(), 'prioridad' => 1],
            ['sucursal_id' => $sucursal->getId(), 'rol_id' => $rolA->getId(), 'prioridad' => 2],
        ], array_map(
            static fn (array $r): array => ['sucursal_id' => (int) $r['sucursal_id'], 'rol_id' => (int) $r['rol_id'], 'prioridad' => (int) $r['prioridad']],
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

    public function testGuardarEditarLimpiarYValidarValoresDePerfilDescriptivo(): void
    {
        $client = static::createClient();
        $usuario = $this->crearUsuario($this->uniqueEmail('pers_perfil'));
        $otroUsuario = $this->crearUsuario($this->uniqueEmail('pers_perfil_otro'));
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $connection = $entityManager->getConnection();

        $attrEscala = (new AtributoPerfil())->setUsuario($usuario)->setNombre('Velocidad')->setTipo(AtributoPerfil::TIPO_ESCALA)->setOrden(1);
        $attrBool = (new AtributoPerfil())->setUsuario($usuario)->setNombre('Disponibilidad fines')->setTipo(AtributoPerfil::TIPO_BOOLEANO)->setOrden(2);
        $attrLista = (new AtributoPerfil())->setUsuario($usuario)->setNombre('Sexo')->setTipo(AtributoPerfil::TIPO_LISTA)->setOpciones(['Femenino', 'Masculino', 'Otro'])->setOrden(3);
        $attrNumero = (new AtributoPerfil())->setUsuario($usuario)->setNombre('Años experiencia')->setTipo(AtributoPerfil::TIPO_NUMERO)->setOrden(4);
        $attrTexto = (new AtributoPerfil())->setUsuario($usuario)->setNombre('Nota')->setTipo(AtributoPerfil::TIPO_TEXTO)->setOrden(5);
        $attrAjeno = (new AtributoPerfil())->setUsuario($otroUsuario)->setNombre('Ajeno')->setTipo(AtributoPerfil::TIPO_TEXTO)->setOrden(1);
        foreach ([$attrEscala, $attrBool, $attrLista, $attrNumero, $attrTexto, $attrAjeno] as $a) {
            $entityManager->persist($a);
        }
        $entityManager->flush();

        $this->login($client, $usuario->getEmail());

        // Alta con los 5 tipos de atributos.
        $crawler = $client->request('GET', '/personal/nuevo');
        self::assertResponseIsSuccessful();
        $csrf = $crawler->filter('input[name="personal[_token]"]')->attr('value');

        $client->request('POST', '/personal/nuevo', [
            'personal' => [
                'nombre' => 'Rosa Perfil',
                'horasSemanales' => '48',
                'color' => '#ffffff',
                'rolesPorSucursal' => '{}',
                '_token' => $csrf,
            ],
            'perfil' => [
                $attrEscala->getId() => '4',
                $attrBool->getId() => '1',
                $attrLista->getId() => 'Femenino',
                $attrNumero->getId() => '3.5',
                $attrTexto->getId() => 'Muy organizada',
            ],
        ]);

        self::assertResponseRedirects('/personal');
        $client->followRedirect();
        $html = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('Atributos de perfil', $html);
        self::assertStringContainsString('4/5', $html);
        self::assertStringContainsString('Femenino', $html);
        self::assertStringContainsString('Muy organizada', $html);

        $personaId = (int) $connection->fetchOne('SELECT id FROM personal WHERE usuario_id = ? AND nombre = ?', [$usuario->getId(), 'Rosa Perfil']);
        self::assertSame(5, (int) $connection->fetchOne('SELECT COUNT(*) FROM valor_perfil WHERE personal_id = ?', [$personaId]));

        // Rechaza valor fuera de rango y atributo ajeno.
        $crawlerEdit = $client->request('GET', '/personal/'.$personaId.'/editar');
        $csrfEdit = $crawlerEdit->filter('input[name="personal[_token]"]')->attr('value');

        $client->request('POST', '/personal/'.$personaId.'/editar', [
            'personal' => [
                'nombre' => 'Rosa Perfil',
                'horasSemanales' => '48',
                'color' => '#ffffff',
                'rolesPorSucursal' => '{}',
                '_token' => $csrfEdit,
            ],
            'perfil' => [
                $attrEscala->getId() => '9',
            ],
        ]);
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Selecciona un valor entre 1 y 5.', (string) $client->getResponse()->getContent());

        $client->request('POST', '/personal/'.$personaId.'/editar', [
            'personal' => [
                'nombre' => 'Rosa Perfil',
                'horasSemanales' => '48',
                'color' => '#ffffff',
                'rolesPorSucursal' => '{}',
                '_token' => $csrfEdit,
            ],
            'perfil' => [
                $attrAjeno->getId() => 'Intruso',
            ],
        ]);
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Uno de los atributos de perfil enviados no es válido.', (string) $client->getResponse()->getContent());

        // Desactiva un atributo con valor existente y verifica que aparece en edición y se puede actualizar/limpiar.
        $connection->executeStatement('UPDATE atributo_perfil SET activo = 0 WHERE id = ?', [$attrTexto->getId()]);
        $crawlerDesact = $client->request('GET', '/personal/'.$personaId.'/editar');
        self::assertStringContainsString('Nota', (string) $client->getResponse()->getContent());
        self::assertStringContainsString('desactivado', (string) $client->getResponse()->getContent());

        $csrfDesact = $crawlerDesact->filter('input[name="personal[_token]"]')->attr('value');
        $client->request('POST', '/personal/'.$personaId.'/editar', [
            'personal' => [
                'nombre' => 'Rosa Perfil',
                'horasSemanales' => '48',
                'color' => '#ffffff',
                'rolesPorSucursal' => '{}',
                '_token' => $csrfDesact,
            ],
            'perfil' => [
                $attrEscala->getId() => '5',
                $attrBool->getId() => '',
                $attrLista->getId() => 'Femenino',
                $attrNumero->getId() => '',
                $attrTexto->getId() => 'Conservada',
            ],
        ]);
        self::assertResponseRedirects('/personal');

        self::assertSame(3, (int) $connection->fetchOne('SELECT COUNT(*) FROM valor_perfil WHERE personal_id = ?', [$personaId]));
        self::assertSame('5', $connection->fetchOne('SELECT valor FROM valor_perfil WHERE personal_id = ? AND atributo_id = ?', [$personaId, $attrEscala->getId()]));
        self::assertSame('Conservada', $connection->fetchOne('SELECT valor FROM valor_perfil WHERE personal_id = ? AND atributo_id = ?', [$personaId, $attrTexto->getId()]));
    }

    private function tokenDeListado(KernelBrowser $client): string
    {
        $client->request('GET', '/personal');
        $html = (string) $client->getResponse()->getContent();
        preg_match('/name="_token" value="([^"]+)"/', $html, $matches);

        return $matches[1];
    }
}
