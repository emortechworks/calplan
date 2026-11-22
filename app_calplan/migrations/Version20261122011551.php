<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261122011551 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE personal (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(120) NOT NULL, horas_semanales SMALLINT NOT NULL, fecha_nacimiento DATE DEFAULT NULL, fin_contrato DATE DEFAULT NULL, activo TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, usuario_id INT NOT NULL, INDEX IDX_F18A6D84DB38439E (usuario_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE personal_tienda (personal_id INT NOT NULL, tienda_id INT NOT NULL, INDEX IDX_344A09295D430949 (personal_id), INDEX IDX_344A092919BA6D46 (tienda_id), PRIMARY KEY (personal_id, tienda_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE personal_rol (id INT AUTO_INCREMENT NOT NULL, prioridad SMALLINT NOT NULL, personal_id INT NOT NULL, rol_id INT NOT NULL, UNIQUE INDEX UNIQ_PERSONAL_ROL (personal_id, rol_id), INDEX IDX_6ADCED995D430949 (personal_id), INDEX IDX_6ADCED994BAB96C (rol_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE personal ADD CONSTRAINT FK_F18A6D84DB38439E FOREIGN KEY (usuario_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE personal_tienda ADD CONSTRAINT FK_344A09295D430949 FOREIGN KEY (personal_id) REFERENCES personal (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE personal_tienda ADD CONSTRAINT FK_344A092919BA6D46 FOREIGN KEY (tienda_id) REFERENCES tienda (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE personal_rol ADD CONSTRAINT FK_6ADCED995D430949 FOREIGN KEY (personal_id) REFERENCES personal (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE personal_rol ADD CONSTRAINT FK_6ADCED994BAB96C FOREIGN KEY (rol_id) REFERENCES rol (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE personal DROP FOREIGN KEY FK_F18A6D84DB38439E');
        $this->addSql('ALTER TABLE personal_tienda DROP FOREIGN KEY FK_344A09295D430949');
        $this->addSql('ALTER TABLE personal_tienda DROP FOREIGN KEY FK_344A092919BA6D46');
        $this->addSql('ALTER TABLE personal_rol DROP FOREIGN KEY FK_6ADCED995D430949');
        $this->addSql('ALTER TABLE personal_rol DROP FOREIGN KEY FK_6ADCED994BAB96C');
        $this->addSql('DROP TABLE personal');
        $this->addSql('DROP TABLE personal_tienda');
        $this->addSql('DROP TABLE personal_rol');
    }
}
