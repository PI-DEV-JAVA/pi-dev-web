<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260420124835 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE applications CHANGE score score DOUBLE PRECISION DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE event CHANGE event_type event_type ENUM(\'MEETUP\',\'CONFERENCE\',\'WORKSHOP\',\'WEBINAR\'), CHANGE status status ENUM(\'UPCOMING\',\'ONGOING\',\'COMPLETED\',\'CANCELLED\')');
        $this->addSql('ALTER TABLE event_participation CHANGE status status ENUM(\'CONFIRMED\',\'PENDING\',\'CANCELLED\',\'ATTENDED\')');
        $this->addSql('ALTER TABLE project ADD time_spent INT DEFAULT 0 NOT NULL, CHANGE status status ENUM(\'PLANNED\',\'IN_PROGRESS\',\'DONE\',\'ON_HOLD\'), CHANGE priority priority ENUM(\'HIGH\',\'MEDIUM\',\'LOW\')');
        $this->addSql('ALTER TABLE seance CHANGE type type ENUM(\'PRESENTIEL\',\'EN_LIGNE\'), CHANGE statut statut ENUM(\'PLANIFIEE\',\'EN_COURS\',\'TERMINEE\')');
        $this->addSql('ALTER TABLE users CHANGE role role ENUM(\'ADMIN\',\'HR\',\'CANDIDATE\'), CHANGE auth_provider auth_provider ENUM(\'LOCAL\',\'GOOGLE\')');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE applications CHANGE score score DOUBLE PRECISION DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE event CHANGE event_type event_type ENUM(\'MEETUP\', \'CONFERENCE\', \'WORKSHOP\', \'WEBINAR\') DEFAULT NULL, CHANGE status status ENUM(\'UPCOMING\', \'ONGOING\', \'COMPLETED\', \'CANCELLED\') DEFAULT NULL');
        $this->addSql('ALTER TABLE event_participation CHANGE status status ENUM(\'CONFIRMED\', \'PENDING\', \'CANCELLED\', \'ATTENDED\') DEFAULT NULL');
        $this->addSql('ALTER TABLE project DROP time_spent, CHANGE status status ENUM(\'PLANNED\', \'IN_PROGRESS\', \'DONE\', \'ON_HOLD\') DEFAULT NULL, CHANGE priority priority ENUM(\'HIGH\', \'MEDIUM\', \'LOW\') DEFAULT NULL');
        $this->addSql('ALTER TABLE seance CHANGE type type ENUM(\'PRESENTIEL\', \'EN_LIGNE\') DEFAULT NULL, CHANGE statut statut ENUM(\'PLANIFIEE\', \'EN_COURS\', \'TERMINEE\') DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE role role ENUM(\'ADMIN\', \'HR\', \'CANDIDATE\') DEFAULT NULL, CHANGE auth_provider auth_provider ENUM(\'LOCAL\', \'GOOGLE\') DEFAULT NULL');
    }
}
