<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260420134547 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE formation ADD CONSTRAINT FK_404021BF156BE243 FOREIGN KEY (recruiter_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_404021BF156BE243 ON formation (recruiter_id)');
        $this->addSql('ALTER TABLE offers CHANGE recruiter_id recruiter_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE offers ADD CONSTRAINT FK_DA460427156BE243 FOREIGN KEY (recruiter_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_DA460427156BE243 ON offers (recruiter_id)');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE60984F51 FOREIGN KEY (project_manager_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_2FB3D0EE60984F51 ON project (project_manager_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE formation DROP FOREIGN KEY FK_404021BF156BE243');
        $this->addSql('DROP INDEX IDX_404021BF156BE243 ON formation');
        $this->addSql('ALTER TABLE offers DROP FOREIGN KEY FK_DA460427156BE243');
        $this->addSql('DROP INDEX IDX_DA460427156BE243 ON offers');
        $this->addSql('ALTER TABLE offers CHANGE recruiter_id recruiter_id INT NOT NULL');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE60984F51');
        $this->addSql('DROP INDEX IDX_2FB3D0EE60984F51 ON project');
    }
}
