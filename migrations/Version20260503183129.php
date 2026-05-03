<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260503183129 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE meets (
              id INT AUTO_INCREMENT NOT NULL,
              title VARCHAR(150) NOT NULL,
              meet_date DATETIME NOT NULL,
              notes LONGTEXT DEFAULT NULL,
              grade DOUBLE PRECISION DEFAULT NULL,
              room_id VARCHAR(50) NOT NULL,
              meet_type VARCHAR(50) DEFAULT 'GENERAL' NOT NULL,
              interview_id INT NOT NULL,
              UNIQUE INDEX UNIQ_673BD66F54177093 (room_id),
              INDEX IDX_673BD66F55D69D95 (interview_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              meets
            ADD
              CONSTRAINT FK_673BD66F55D69D95 FOREIGN KEY (interview_id) REFERENCES interviews (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE applications DROP FOREIGN KEY FK_F7C966F053C674EE');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              applications
            ADD
              workflow JSON DEFAULT NULL,
            CHANGE
              score score DOUBLE PRECISION DEFAULT 0 NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              applications
            ADD
              CONSTRAINT FK_F7C966F053C674EE FOREIGN KEY (offer_id) REFERENCES offers (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE bookmarks DROP FOREIGN KEY FK_78D2C14053C674EE');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              bookmarks
            ADD
              CONSTRAINT FK_78D2C14053C674EE FOREIGN KEY (offer_id) REFERENCES offers (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              event
            CHANGE
              event_type event_type ENUM(
                'MEETUP', 'CONFERENCE', 'WORKSHOP',
                'WEBINAR'
              ),
            CHANGE
              status status ENUM(
                'UPCOMING', 'ONGOING', 'COMPLETED',
                'CANCELLED'
              )
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              event_participation
            CHANGE
              status status ENUM(
                'CONFIRMED', 'PENDING', 'CANCELLED',
                'ATTENDED'
              )
        SQL);
        $this->addSql('ALTER TABLE interviews DROP FOREIGN KEY FK_3A7526823E030ACD');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              interviews
            ADD
              CONSTRAINT FK_3A7526823E030ACD FOREIGN KEY (application_id) REFERENCES applications (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE offers ADD workflow JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE project CHANGE status status ENUM(\'PLANNED\',\'IN_PROGRESS\',\'DONE\',\'ON_HOLD\')');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              seance
            CHANGE
              type type ENUM('PRESENTIEL', 'EN_LIGNE'),
            CHANGE
              statut statut ENUM(
                'PLANIFIEE', 'EN_COURS', 'TERMINEE'
              )
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              users
            CHANGE
              role role ENUM('ADMIN', 'HR', 'CANDIDATE'),
            CHANGE
              auth_provider auth_provider ENUM('LOCAL', 'GOOGLE')
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE meets DROP FOREIGN KEY FK_673BD66F55D69D95');
        $this->addSql('DROP TABLE meets');
        $this->addSql('ALTER TABLE applications DROP FOREIGN KEY FK_F7C966F053C674EE');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              applications
            DROP
              workflow,
            CHANGE
              score score DOUBLE PRECISION DEFAULT '0' NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              applications
            ADD
              CONSTRAINT FK_F7C966F053C674EE FOREIGN KEY (offer_id) REFERENCES offers (id) ON
            UPDATE
              NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql('ALTER TABLE bookmarks DROP FOREIGN KEY FK_78D2C14053C674EE');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              bookmarks
            ADD
              CONSTRAINT FK_78D2C14053C674EE FOREIGN KEY (offer_id) REFERENCES offers (id) ON
            UPDATE
              NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              event
            CHANGE
              event_type event_type ENUM(
                'MEETUP', 'CONFERENCE', 'WORKSHOP',
                'WEBINAR'
              ) DEFAULT NULL,
            CHANGE
              status status ENUM(
                'UPCOMING', 'ONGOING', 'COMPLETED',
                'CANCELLED'
              ) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              event_participation
            CHANGE
              status status ENUM(
                'CONFIRMED', 'PENDING', 'CANCELLED',
                'ATTENDED'
              ) DEFAULT NULL
        SQL);
        $this->addSql('ALTER TABLE interviews DROP FOREIGN KEY FK_3A7526823E030ACD');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              interviews
            ADD
              CONSTRAINT FK_3A7526823E030ACD FOREIGN KEY (application_id) REFERENCES applications (id) ON
            UPDATE
              NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql('ALTER TABLE offers DROP workflow');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              project
            CHANGE
              status status ENUM(
                'PLANNED', 'IN_PROGRESS', 'DONE',
                'ON_HOLD'
              ) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              seance
            CHANGE
              type type ENUM('PRESENTIEL', 'EN_LIGNE') DEFAULT NULL,
            CHANGE
              statut statut ENUM(
                'PLANIFIEE', 'EN_COURS', 'TERMINEE'
              ) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              users
            CHANGE
              role role ENUM('ADMIN', 'HR', 'CANDIDATE') DEFAULT NULL,
            CHANGE
              auth_provider auth_provider ENUM('LOCAL', 'GOOGLE') DEFAULT NULL
        SQL);
    }
}
