<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Renombra las tablas y columnas de tienda a sucursal preservando todos los datos existentes.
 */
final class Version20261122174912 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renombra tienda, tienda_rol, personal_tienda y personal_rol.tienda_id a sucursal preservando los datos.';
    }

    public function up(Schema $schema): void
    {
        // Quita FKs previas antes de renombrar tablas, columnas e índices.
        $this->addSql('ALTER TABLE personal_rol DROP FOREIGN KEY FK_6ADCED9919BA6D46');
        $this->addSql('ALTER TABLE personal_tienda DROP FOREIGN KEY FK_344A092919BA6D46');
        $this->addSql('ALTER TABLE personal_tienda DROP FOREIGN KEY FK_344A09295D430949');
        $this->addSql('ALTER TABLE tienda_rol DROP FOREIGN KEY FK_FA27096C19BA6D46');
        $this->addSql('ALTER TABLE tienda_rol DROP FOREIGN KEY FK_FA27096C4BAB96C');
        $this->addSql('ALTER TABLE tienda DROP FOREIGN KEY FK_C0C6E53EDB38439E');

        // Renombra tablas en sitio para preservar el 100 % de los registros.
        $this->addSql('RENAME TABLE tienda TO sucursal, tienda_rol TO sucursal_rol, personal_tienda TO personal_sucursal');

        $this->addSql('ALTER TABLE sucursal RENAME INDEX UNIQ_TIENDA_NOMBRE TO UNIQ_SUCURSAL_NOMBRE');
        $this->addSql('ALTER TABLE sucursal RENAME INDEX UNIQ_TIENDA_CODIGO TO UNIQ_SUCURSAL_CODIGO');
        $this->addSql('ALTER TABLE sucursal RENAME INDEX IDX_C0C6E53EDB38439E TO IDX_E99C6D56DB38439E');
        $this->addSql('ALTER TABLE sucursal ADD CONSTRAINT FK_E99C6D56DB38439E FOREIGN KEY (usuario_id) REFERENCES user (id)');

        $this->addSql('ALTER TABLE sucursal_rol CHANGE tienda_id sucursal_id INT NOT NULL');
        $this->addSql('ALTER TABLE sucursal_rol RENAME INDEX IDX_FA27096C19BA6D46 TO IDX_E975B10A279A5D5E');
        $this->addSql('ALTER TABLE sucursal_rol RENAME INDEX IDX_FA27096C4BAB96C TO IDX_E975B10A4BAB96C');
        $this->addSql('ALTER TABLE sucursal_rol ADD CONSTRAINT FK_E975B10A279A5D5E FOREIGN KEY (sucursal_id) REFERENCES sucursal (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sucursal_rol ADD CONSTRAINT FK_E975B10A4BAB96C FOREIGN KEY (rol_id) REFERENCES rol (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE personal_sucursal CHANGE tienda_id sucursal_id INT NOT NULL');
        $this->addSql('ALTER TABLE personal_sucursal RENAME INDEX IDX_344A09295D430949 TO IDX_45A3B31F5D430949');
        $this->addSql('ALTER TABLE personal_sucursal RENAME INDEX IDX_344A092919BA6D46 TO IDX_45A3B31F279A5D5E');
        $this->addSql('ALTER TABLE personal_sucursal ADD CONSTRAINT FK_45A3B31F5D430949 FOREIGN KEY (personal_id) REFERENCES personal (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE personal_sucursal ADD CONSTRAINT FK_45A3B31F279A5D5E FOREIGN KEY (sucursal_id) REFERENCES sucursal (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE personal_rol CHANGE tienda_id sucursal_id INT NOT NULL');
        $this->addSql('ALTER TABLE personal_rol RENAME INDEX IDX_6ADCED9919BA6D46 TO IDX_6ADCED99279A5D5E');
        $this->addSql('ALTER TABLE personal_rol RENAME INDEX UNIQ_PERSONAL_TIENDA_ROL TO UNIQ_PERSONAL_SUCURSAL_ROL');
        $this->addSql('ALTER TABLE personal_rol ADD CONSTRAINT FK_6ADCED99279A5D5E FOREIGN KEY (sucursal_id) REFERENCES sucursal (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE personal_rol DROP FOREIGN KEY FK_6ADCED99279A5D5E');
        $this->addSql('ALTER TABLE personal_sucursal DROP FOREIGN KEY FK_45A3B31F279A5D5E');
        $this->addSql('ALTER TABLE personal_sucursal DROP FOREIGN KEY FK_45A3B31F5D430949');
        $this->addSql('ALTER TABLE sucursal_rol DROP FOREIGN KEY FK_E975B10A279A5D5E');
        $this->addSql('ALTER TABLE sucursal_rol DROP FOREIGN KEY FK_E975B10A4BAB96C');
        $this->addSql('ALTER TABLE sucursal DROP FOREIGN KEY FK_E99C6D56DB38439E');

        $this->addSql('RENAME TABLE sucursal TO tienda, sucursal_rol TO tienda_rol, personal_sucursal TO personal_tienda');

        $this->addSql('ALTER TABLE tienda RENAME INDEX UNIQ_SUCURSAL_NOMBRE TO UNIQ_TIENDA_NOMBRE');
        $this->addSql('ALTER TABLE tienda RENAME INDEX UNIQ_SUCURSAL_CODIGO TO UNIQ_TIENDA_CODIGO');
        $this->addSql('ALTER TABLE tienda RENAME INDEX IDX_E99C6D56DB38439E TO IDX_C0C6E53EDB38439E');
        $this->addSql('ALTER TABLE tienda ADD CONSTRAINT FK_C0C6E53EDB38439E FOREIGN KEY (usuario_id) REFERENCES user (id)');

        $this->addSql('ALTER TABLE tienda_rol CHANGE sucursal_id tienda_id INT NOT NULL');
        $this->addSql('ALTER TABLE tienda_rol RENAME INDEX IDX_E975B10A279A5D5E TO IDX_FA27096C19BA6D46');
        $this->addSql('ALTER TABLE tienda_rol RENAME INDEX IDX_E975B10A4BAB96C TO IDX_FA27096C4BAB96C');
        $this->addSql('ALTER TABLE tienda_rol ADD CONSTRAINT FK_FA27096C19BA6D46 FOREIGN KEY (tienda_id) REFERENCES tienda (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tienda_rol ADD CONSTRAINT FK_FA27096C4BAB96C FOREIGN KEY (rol_id) REFERENCES rol (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE personal_tienda CHANGE sucursal_id tienda_id INT NOT NULL');
        $this->addSql('ALTER TABLE personal_tienda RENAME INDEX IDX_45A3B31F5D430949 TO IDX_344A09295D430949');
        $this->addSql('ALTER TABLE personal_tienda RENAME INDEX IDX_45A3B31F279A5D5E TO IDX_344A092919BA6D46');
        $this->addSql('ALTER TABLE personal_tienda ADD CONSTRAINT FK_344A09295D430949 FOREIGN KEY (personal_id) REFERENCES personal (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE personal_tienda ADD CONSTRAINT FK_344A092919BA6D46 FOREIGN KEY (tienda_id) REFERENCES tienda (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE personal_rol CHANGE sucursal_id tienda_id INT NOT NULL');
        $this->addSql('ALTER TABLE personal_rol RENAME INDEX IDX_6ADCED99279A5D5E TO IDX_6ADCED9919BA6D46');
        $this->addSql('ALTER TABLE personal_rol RENAME INDEX UNIQ_PERSONAL_SUCURSAL_ROL TO UNIQ_PERSONAL_TIENDA_ROL');
        $this->addSql('ALTER TABLE personal_rol ADD CONSTRAINT FK_6ADCED9919BA6D46 FOREIGN KEY (tienda_id) REFERENCES tienda (id) ON DELETE CASCADE');
    }
}
