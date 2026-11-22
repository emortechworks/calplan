<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261122165530 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE tienda_rol (tienda_id INT NOT NULL, rol_id INT NOT NULL, INDEX IDX_FA27096C19BA6D46 (tienda_id), INDEX IDX_FA27096C4BAB96C (rol_id), PRIMARY KEY (tienda_id, rol_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE tienda_rol ADD CONSTRAINT FK_FA27096C19BA6D46 FOREIGN KEY (tienda_id) REFERENCES tienda (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tienda_rol ADD CONSTRAINT FK_FA27096C4BAB96C FOREIGN KEY (rol_id) REFERENCES rol (id) ON DELETE CASCADE');

        // Conserva asignaciones previas: vincula a cada tienda los roles que ya tenía su personal.
        $this->addSql('INSERT IGNORE INTO tienda_rol (tienda_id, rol_id) SELECT DISTINCT pt.tienda_id, pr.rol_id FROM personal_rol pr INNER JOIN personal_tienda pt ON pt.personal_id = pr.personal_id');

        $this->addSql('DROP INDEX UNIQ_PERSONAL_ROL ON personal_rol');
        $this->addSql('ALTER TABLE personal_rol ADD tienda_id INT DEFAULT NULL');
        $this->addSql('UPDATE personal_rol pr INNER JOIN (SELECT personal_id, MIN(tienda_id) AS tienda_id FROM personal_tienda GROUP BY personal_id) pt ON pt.personal_id = pr.personal_id SET pr.tienda_id = pt.tienda_id');
        $this->addSql('INSERT INTO personal_rol (personal_id, tienda_id, rol_id, prioridad) SELECT pr.personal_id, pt.tienda_id, pr.rol_id, pr.prioridad FROM personal_rol pr INNER JOIN personal_tienda pt ON pt.personal_id = pr.personal_id WHERE pr.tienda_id IS NOT NULL AND pt.tienda_id <> pr.tienda_id');
        $this->addSql('DELETE FROM personal_rol WHERE tienda_id IS NULL');
        $this->addSql('ALTER TABLE personal_rol MODIFY tienda_id INT NOT NULL');
        $this->addSql('ALTER TABLE personal_rol ADD CONSTRAINT FK_6ADCED9919BA6D46 FOREIGN KEY (tienda_id) REFERENCES tienda (id) ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_PERSONAL_TIENDA_ROL ON personal_rol (personal_id, tienda_id, rol_id)');
        $this->addSql('CREATE INDEX IDX_6ADCED9919BA6D46 ON personal_rol (tienda_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tienda_rol DROP FOREIGN KEY FK_FA27096C19BA6D46');
        $this->addSql('ALTER TABLE tienda_rol DROP FOREIGN KEY FK_FA27096C4BAB96C');
        $this->addSql('DROP TABLE tienda_rol');
        $this->addSql('ALTER TABLE personal_rol DROP FOREIGN KEY FK_6ADCED9919BA6D46');
        $this->addSql('DROP INDEX UNIQ_PERSONAL_TIENDA_ROL ON personal_rol');
        $this->addSql('DROP INDEX IDX_6ADCED9919BA6D46 ON personal_rol');
        $this->addSql('ALTER TABLE personal_rol DROP tienda_id');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_PERSONAL_ROL ON personal_rol (personal_id, rol_id)');
    }
}
