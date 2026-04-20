<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260417032529 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE formation_enrollment (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(20) DEFAULT \'PENDING\' NOT NULL, requested_at DATETIME NOT NULL, responded_at DATETIME DEFAULT NULL, user_id INT NOT NULL, formation_id INT NOT NULL, INDEX IDX_237404D8A76ED395 (user_id), INDEX IDX_237404D85200282E (formation_id), UNIQUE INDEX unique_user_formation (user_id, formation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE quiz_attempt (id INT AUTO_INCREMENT NOT NULL, score DOUBLE PRECISION DEFAULT 0 NOT NULL, tab_switch_count INT DEFAULT 0 NOT NULL, cheated TINYINT(1) DEFAULT 0 NOT NULL, submitted_at DATETIME NOT NULL, user_id INT NOT NULL, quiz_id INT NOT NULL, INDEX IDX_AB6AFC6A76ED395 (user_id), INDEX IDX_AB6AFC6853CD175 (quiz_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE formation_enrollment ADD CONSTRAINT FK_237404D8A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE formation_enrollment ADD CONSTRAINT FK_237404D85200282E FOREIGN KEY (formation_id) REFERENCES formation (id)');
        $this->addSql('ALTER TABLE quiz_attempt ADD CONSTRAINT FK_AB6AFC6A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE quiz_attempt ADD CONSTRAINT FK_AB6AFC6853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)');
        $this->addSql('DROP TABLE activity_files');
        $this->addSql('DROP TABLE activity_tracking_history');
        $this->addSql('DROP TABLE inscription');
        $this->addSql('DROP TABLE interview');
        $this->addSql('DROP TABLE interview_meet');
        $this->addSql('DROP TABLE interview_note');
        $this->addSql('DROP TABLE tentative_quiz');
        $this->addSql('DROP TABLE tentative_seance');
        $this->addSql('DROP TABLE user_skills');
        $this->addSql('ALTER TABLE applications CHANGE score score DOUBLE PRECISION DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE event CHANGE event_type event_type ENUM(\'MEETUP\',\'CONFERENCE\',\'WORKSHOP\',\'WEBINAR\'), CHANGE status status ENUM(\'UPCOMING\',\'ONGOING\',\'COMPLETED\',\'CANCELLED\')');
        $this->addSql('ALTER TABLE event_participation CHANGE status status ENUM(\'CONFIRMED\',\'PENDING\',\'CANCELLED\',\'ATTENDED\')');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D3A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE project CHANGE status status ENUM(\'PLANNED\',\'IN_PROGRESS\',\'DONE\',\'ON_HOLD\')');
        $this->addSql('ALTER TABLE quiz ADD seance_id INT DEFAULT NULL, CHANGE formation_id formation_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE quiz ADD CONSTRAINT FK_A412FA92E3797A94 FOREIGN KEY (seance_id) REFERENCES seance (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A412FA92E3797A94 ON quiz (seance_id)');
        $this->addSql('ALTER TABLE seance CHANGE type type ENUM(\'PRESENTIEL\',\'EN_LIGNE\'), CHANGE statut statut ENUM(\'PLANIFIEE\',\'EN_COURS\',\'TERMINEE\')');
        $this->addSql('ALTER TABLE users CHANGE role role ENUM(\'ADMIN\',\'HR\',\'CANDIDATE\'), CHANGE auth_provider auth_provider ENUM(\'LOCAL\',\'GOOGLE\')');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE activity_files (id INT NOT NULL, activity_id INT NOT NULL, file_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, file_path VARCHAR(500) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, file_size BIGINT NOT NULL, file_type VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, uploaded_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = MyISAM COMMENT = \'\' ');
        $this->addSql('CREATE TABLE activity_tracking_history (id INT NOT NULL, activity_id INT NOT NULL, session_start DATETIME NOT NULL, session_end DATETIME NOT NULL, seconds_tracked INT NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = MyISAM COMMENT = \'\' ');
        $this->addSql('CREATE TABLE inscription (id INT NOT NULL, formation_id INT DEFAULT NULL, user_id INT NOT NULL, candidat_nom VARCHAR(120) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, candidat_email VARCHAR(120) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, date_inscription DATETIME NOT NULL, statut VARCHAR(30) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, score_quiz DOUBLE PRECISION NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_5E90F6D65200282E (formation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = MyISAM COMMENT = \'\' ');
        $this->addSql('CREATE TABLE interview (id BIGINT NOT NULL, title VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, recruiter_id BIGINT NOT NULL, candidate_id BIGINT NOT NULL, status VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, general_grade DOUBLE PRECISION NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = MyISAM COMMENT = \'\' ');
        $this->addSql('CREATE TABLE interview_meet (id BIGINT NOT NULL, interview_id BIGINT NOT NULL, meet_uuid VARCHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, scheduled_at DATETIME NOT NULL, status VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, grade DOUBLE PRECISION NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = MyISAM COMMENT = \'\' ');
        $this->addSql('CREATE TABLE interview_note (id BIGINT NOT NULL, interview_id BIGINT NOT NULL, recruiter_id BIGINT NOT NULL, note LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = MyISAM COMMENT = \'\' ');
        $this->addSql('CREATE TABLE tentative_quiz (id INT NOT NULL, quiz_id INT DEFAULT NULL, user_id INT NOT NULL, candidat_nom VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, candidat_email VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, score INT NOT NULL, total INT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_A66F2D8853CD175 (quiz_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = MyISAM COMMENT = \'\' ');
        $this->addSql('CREATE TABLE tentative_seance (id INT NOT NULL, seance_id INT DEFAULT NULL, quiz_id INT DEFAULT NULL, user_id INT NOT NULL, candidat_email VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, score INT NOT NULL, total INT NOT NULL, statut VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, started_at DATETIME NOT NULL, ended_at DATETIME NOT NULL, INDEX IDX_DB307CC7E3797A94 (seance_id), INDEX IDX_DB307CC7853CD175 (quiz_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = MyISAM COMMENT = \'\' ');
        $this->addSql('CREATE TABLE user_skills (id INT NOT NULL, user_id INT NOT NULL, skill VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = MyISAM COMMENT = \'\' ');
        $this->addSql('ALTER TABLE formation_enrollment DROP FOREIGN KEY FK_237404D8A76ED395');
        $this->addSql('ALTER TABLE formation_enrollment DROP FOREIGN KEY FK_237404D85200282E');
        $this->addSql('ALTER TABLE quiz_attempt DROP FOREIGN KEY FK_AB6AFC6A76ED395');
        $this->addSql('ALTER TABLE quiz_attempt DROP FOREIGN KEY FK_AB6AFC6853CD175');
        $this->addSql('DROP TABLE formation_enrollment');
        $this->addSql('DROP TABLE quiz_attempt');
        $this->addSql('ALTER TABLE applications CHANGE score score DOUBLE PRECISION DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE event CHANGE event_type event_type ENUM(\'MEETUP\', \'CONFERENCE\', \'WORKSHOP\', \'WEBINAR\') DEFAULT NULL, CHANGE status status ENUM(\'UPCOMING\', \'ONGOING\', \'COMPLETED\', \'CANCELLED\') DEFAULT NULL');
        $this->addSql('ALTER TABLE event_participation CHANGE status status ENUM(\'CONFIRMED\', \'PENDING\', \'CANCELLED\', \'ATTENDED\') DEFAULT NULL');
        $this->addSql('ALTER TABLE notifications DROP FOREIGN KEY FK_6000B0D3A76ED395');
        $this->addSql('ALTER TABLE project CHANGE status status ENUM(\'PLANNED\', \'IN_PROGRESS\', \'DONE\', \'ON_HOLD\') DEFAULT NULL');
        $this->addSql('ALTER TABLE quiz DROP FOREIGN KEY FK_A412FA92E3797A94');
        $this->addSql('DROP INDEX UNIQ_A412FA92E3797A94 ON quiz');
        $this->addSql('ALTER TABLE quiz DROP seance_id, CHANGE formation_id formation_id INT NOT NULL');
        $this->addSql('ALTER TABLE seance CHANGE type type ENUM(\'PRESENTIEL\', \'EN_LIGNE\') DEFAULT NULL, CHANGE statut statut ENUM(\'PLANIFIEE\', \'EN_COURS\', \'TERMINEE\') DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE role role ENUM(\'ADMIN\', \'HR\', \'CANDIDATE\') DEFAULT NULL, CHANGE auth_provider auth_provider ENUM(\'LOCAL\', \'GOOGLE\') DEFAULT NULL');
    }
}
