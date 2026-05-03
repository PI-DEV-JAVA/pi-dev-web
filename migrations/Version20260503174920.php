<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260503174920 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE activities (id_activity INT AUTO_INCREMENT NOT NULL, activity_date DATE NOT NULL, description LONGTEXT DEFAULT NULL, hours_worked NUMERIC(5, 2) DEFAULT NULL, user_report LONGTEXT DEFAULT NULL, report_status VARCHAR(20) DEFAULT \'PENDING\' NOT NULL, expected_deadline DATETIME DEFAULT NULL, is_late_submission TINYINT(1) DEFAULT 0 NOT NULL, delay_in_hours INT DEFAULT 0 NOT NULL, admin_feedback LONGTEXT DEFAULT NULL, revision_count INT DEFAULT 0 NOT NULL, employee_id INT NOT NULL, project_id INT DEFAULT NULL, INDEX IDX_B5F1AFE58C03F15C (employee_id), INDEX IDX_B5F1AFE5166D1F9C (project_id), PRIMARY KEY(id_activity)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE applications (id INT AUTO_INCREMENT NOT NULL, cv_file_path VARCHAR(500) DEFAULT NULL, motivation_letter LONGTEXT DEFAULT NULL, status VARCHAR(50) DEFAULT \'Nouvelle\' NOT NULL, application_date DATE DEFAULT NULL, score DOUBLE PRECISION DEFAULT 0 NOT NULL, notes LONGTEXT DEFAULT NULL, interviewer VARCHAR(150) DEFAULT NULL, interview_date DATE DEFAULT NULL, interview_result VARCHAR(100) DEFAULT NULL, recruiter_response LONGTEXT DEFAULT NULL, response_date DATE DEFAULT NULL, workflow JSON DEFAULT NULL, user_id INT NOT NULL, offer_id INT NOT NULL, INDEX IDX_F7C966F0A76ED395 (user_id), INDEX IDX_F7C966F053C674EE (offer_id), UNIQUE INDEX unique_user_offer (user_id, offer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE bookmarks (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, user_id INT NOT NULL, offer_id INT NOT NULL, INDEX IDX_78D2C140A76ED395 (user_id), INDEX IDX_78D2C14053C674EE (offer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE choix (id INT AUTO_INCREMENT NOT NULL, texte VARCHAR(500) NOT NULL, is_correct TINYINT(1) DEFAULT 0 NOT NULL, question_id INT NOT NULL, INDEX IDX_4F4880911E27F6BF (question_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE event (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, event_type ENUM(\'MEETUP\',\'CONFERENCE\',\'WORKSHOP\',\'WEBINAR\'), event_date DATETIME NOT NULL, end_date DATETIME DEFAULT NULL, location VARCHAR(500) DEFAULT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, is_online TINYINT(1) DEFAULT 0 NOT NULL, online_link VARCHAR(500) DEFAULT NULL, max_capacity INT DEFAULT 0 NOT NULL, cover_image VARCHAR(500) DEFAULT NULL, status ENUM(\'UPCOMING\',\'ONGOING\',\'COMPLETED\',\'CANCELLED\'), created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, organizer_id INT NOT NULL, INDEX IDX_3BAE0AA7876C4DDA (organizer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE event_comment (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, event_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_1123FBC371F7E88B (event_id), INDEX IDX_1123FBC3A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE event_feedback (id INT AUTO_INCREMENT NOT NULL, rating INT NOT NULL, comment LONGTEXT DEFAULT NULL, would_recommend TINYINT(1) DEFAULT 1 NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, participation_id INT NOT NULL, UNIQUE INDEX UNIQ_94C5AD886ACE3B73 (participation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE event_like (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, event_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_B3A80C1871F7E88B (event_id), INDEX IDX_B3A80C18A76ED395 (user_id), UNIQUE INDEX unique_like (event_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE event_participation (id INT AUTO_INCREMENT NOT NULL, status ENUM(\'CONFIRMED\',\'PENDING\',\'CANCELLED\',\'ATTENDED\'), registered_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, qr_code VARCHAR(500) DEFAULT NULL, event_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_8F0C52E371F7E88B (event_id), INDEX IDX_8F0C52E3A76ED395 (user_id), UNIQUE INDEX unique_participation (event_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE formation (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, niveau VARCHAR(100) DEFAULT NULL, duree INT DEFAULT NULL, date_debut DATE DEFAULT NULL, date_fin DATE DEFAULT NULL, recruiter_id INT DEFAULT NULL, INDEX IDX_404021BF156BE243 (recruiter_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE interviews (id INT AUTO_INCREMENT NOT NULL, interview_date DATETIME NOT NULL, status VARCHAR(50) DEFAULT \'SCHEDULED\' NOT NULL, notes LONGTEXT DEFAULT NULL, location VARCHAR(100) DEFAULT NULL, meeting_link VARCHAR(500) DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, application_id INT NOT NULL, INDEX IDX_3A7526823E030ACD (application_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE meets (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(150) NOT NULL, meet_date DATETIME NOT NULL, notes LONGTEXT DEFAULT NULL, grade DOUBLE PRECISION DEFAULT NULL, room_id VARCHAR(50) NOT NULL, meet_type VARCHAR(50) DEFAULT \'GENERAL\' NOT NULL, interview_id INT NOT NULL, UNIQUE INDEX UNIQ_673BD66F54177093 (room_id), INDEX IDX_673BD66F55D69D95 (interview_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE notifications (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, title VARCHAR(255) NOT NULL, message LONGTEXT DEFAULT NULL, link VARCHAR(500) DEFAULT NULL, is_read TINYINT(1) DEFAULT 0 NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, user_id INT NOT NULL, INDEX IDX_6000B0D3A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE offers (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, department VARCHAR(100) DEFAULT NULL, contract_type VARCHAR(50) DEFAULT NULL, experience_level VARCHAR(50) DEFAULT NULL, salary_min DOUBLE PRECISION DEFAULT NULL, salary_max DOUBLE PRECISION DEFAULT NULL, location VARCHAR(150) DEFAULT NULL, status VARCHAR(50) DEFAULT NULL, publish_date DATE DEFAULT NULL, closing_date DATE DEFAULT NULL, positions_available INT DEFAULT 1 NOT NULL, applications_received INT DEFAULT 0 NOT NULL, workflow JSON DEFAULT NULL, recruiter_id INT DEFAULT NULL, INDEX IDX_DA460427156BE243 (recruiter_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE profiles (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(50) DEFAULT NULL, last_name VARCHAR(50) DEFAULT NULL, birth_date DATE DEFAULT NULL, phone_number VARCHAR(20) DEFAULT NULL, location VARCHAR(100) DEFAULT NULL, professional_title VARCHAR(100) DEFAULT NULL, years_of_experience INT DEFAULT NULL, summary LONGTEXT DEFAULT NULL, profile_completed TINYINT(1) NOT NULL, profile_picture_path VARCHAR(500) DEFAULT NULL, cv_path VARCHAR(500) DEFAULT NULL, user_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_8B308530A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE project (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, status ENUM(\'PLANNED\',\'IN_PROGRESS\',\'DONE\',\'ON_HOLD\'), start_date DATE DEFAULT NULL, end_date DATE DEFAULT NULL, budget NUMERIC(10, 2) DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, project_manager_id INT DEFAULT NULL, INDEX IDX_2FB3D0EE60984F51 (project_manager_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE question (id INT AUTO_INCREMENT NOT NULL, enonce VARCHAR(500) NOT NULL, quiz_id INT NOT NULL, INDEX IDX_B6F7494E853CD175 (quiz_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE quiz (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, duree INT DEFAULT NULL, formation_id INT NOT NULL, INDEX IDX_A412FA925200282E (formation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE seance (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(150) NOT NULL, type ENUM(\'PRESENTIEL\',\'EN_LIGNE\'), date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, adresse VARCHAR(255) DEFAULT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, video_path VARCHAR(255) DEFAULT NULL, duree_minutes INT DEFAULT NULL, statut ENUM(\'PLANIFIEE\',\'EN_COURS\',\'TERMINEE\'), created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, formation_id INT NOT NULL, INDEX IDX_DF7DFD0E5200282E (formation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE support_tickets (id INT AUTO_INCREMENT NOT NULL, subject VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, status VARCHAR(50) DEFAULT \'OPEN\' NOT NULL, priority VARCHAR(50) DEFAULT \'MEDIUM\' NOT NULL, category VARCHAR(100) DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_E9739508A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE sync_messages (id INT AUTO_INCREMENT NOT NULL, message LONGTEXT NOT NULL, is_read TINYINT(1) DEFAULT 0 NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, sync_id INT NOT NULL, sender_id INT NOT NULL, INDEX IDX_9A10A0B8FA50C422 (sync_id), INDEX IDX_9A10A0B8F624B39D (sender_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE syncs (id INT AUTO_INCREMENT NOT NULL, reason VARCHAR(50) DEFAULT \'NETWORK\' NOT NULL, status VARCHAR(50) DEFAULT \'PENDING\' NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, accepted_at DATETIME DEFAULT NULL, sender_id INT NOT NULL, receiver_id INT NOT NULL, INDEX IDX_ABF27CDCF624B39D (sender_id), INDEX IDX_ABF27CDCCD53EDB6 (receiver_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ticket_replies (id INT AUTO_INCREMENT NOT NULL, message LONGTEXT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ticket_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_ACCC3E78700047D2 (ticket_id), INDEX IDX_ACCC3E78A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(190) NOT NULL, password_hash VARCHAR(255) DEFAULT NULL, role ENUM(\'ADMIN\',\'HR\',\'CANDIDATE\'), active TINYINT(1) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, auth_provider ENUM(\'LOCAL\',\'GOOGLE\'), provider_id VARCHAR(255) DEFAULT NULL, email_verified TINYINT(1) NOT NULL, failed_attempts INT DEFAULT 0 NOT NULL, reset_token VARCHAR(100) DEFAULT NULL, reset_token_expires_at DATETIME DEFAULT NULL, verification_token VARCHAR(100) DEFAULT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE activities ADD CONSTRAINT FK_B5F1AFE58C03F15C FOREIGN KEY (employee_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE activities ADD CONSTRAINT FK_B5F1AFE5166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE applications ADD CONSTRAINT FK_F7C966F0A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE applications ADD CONSTRAINT FK_F7C966F053C674EE FOREIGN KEY (offer_id) REFERENCES offers (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bookmarks ADD CONSTRAINT FK_78D2C140A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE bookmarks ADD CONSTRAINT FK_78D2C14053C674EE FOREIGN KEY (offer_id) REFERENCES offers (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE choix ADD CONSTRAINT FK_4F4880911E27F6BF FOREIGN KEY (question_id) REFERENCES question (id)');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7876C4DDA FOREIGN KEY (organizer_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE event_comment ADD CONSTRAINT FK_1123FBC371F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_comment ADD CONSTRAINT FK_1123FBC3A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE event_feedback ADD CONSTRAINT FK_94C5AD886ACE3B73 FOREIGN KEY (participation_id) REFERENCES event_participation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_like ADD CONSTRAINT FK_B3A80C1871F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_like ADD CONSTRAINT FK_B3A80C18A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE event_participation ADD CONSTRAINT FK_8F0C52E371F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_participation ADD CONSTRAINT FK_8F0C52E3A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE formation ADD CONSTRAINT FK_404021BF156BE243 FOREIGN KEY (recruiter_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE interviews ADD CONSTRAINT FK_3A7526823E030ACD FOREIGN KEY (application_id) REFERENCES applications (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE meets ADD CONSTRAINT FK_673BD66F55D69D95 FOREIGN KEY (interview_id) REFERENCES interviews (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D3A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE offers ADD CONSTRAINT FK_DA460427156BE243 FOREIGN KEY (recruiter_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE profiles ADD CONSTRAINT FK_8B308530A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE60984F51 FOREIGN KEY (project_manager_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494E853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)');
        $this->addSql('ALTER TABLE quiz ADD CONSTRAINT FK_A412FA925200282E FOREIGN KEY (formation_id) REFERENCES formation (id)');
        $this->addSql('ALTER TABLE seance ADD CONSTRAINT FK_DF7DFD0E5200282E FOREIGN KEY (formation_id) REFERENCES formation (id)');
        $this->addSql('ALTER TABLE support_tickets ADD CONSTRAINT FK_E9739508A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE sync_messages ADD CONSTRAINT FK_9A10A0B8FA50C422 FOREIGN KEY (sync_id) REFERENCES syncs (id)');
        $this->addSql('ALTER TABLE sync_messages ADD CONSTRAINT FK_9A10A0B8F624B39D FOREIGN KEY (sender_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE syncs ADD CONSTRAINT FK_ABF27CDCF624B39D FOREIGN KEY (sender_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE syncs ADD CONSTRAINT FK_ABF27CDCCD53EDB6 FOREIGN KEY (receiver_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE ticket_replies ADD CONSTRAINT FK_ACCC3E78700047D2 FOREIGN KEY (ticket_id) REFERENCES support_tickets (id)');
        $this->addSql('ALTER TABLE ticket_replies ADD CONSTRAINT FK_ACCC3E78A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activities DROP FOREIGN KEY FK_B5F1AFE58C03F15C');
        $this->addSql('ALTER TABLE activities DROP FOREIGN KEY FK_B5F1AFE5166D1F9C');
        $this->addSql('ALTER TABLE applications DROP FOREIGN KEY FK_F7C966F0A76ED395');
        $this->addSql('ALTER TABLE applications DROP FOREIGN KEY FK_F7C966F053C674EE');
        $this->addSql('ALTER TABLE bookmarks DROP FOREIGN KEY FK_78D2C140A76ED395');
        $this->addSql('ALTER TABLE bookmarks DROP FOREIGN KEY FK_78D2C14053C674EE');
        $this->addSql('ALTER TABLE choix DROP FOREIGN KEY FK_4F4880911E27F6BF');
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7876C4DDA');
        $this->addSql('ALTER TABLE event_comment DROP FOREIGN KEY FK_1123FBC371F7E88B');
        $this->addSql('ALTER TABLE event_comment DROP FOREIGN KEY FK_1123FBC3A76ED395');
        $this->addSql('ALTER TABLE event_feedback DROP FOREIGN KEY FK_94C5AD886ACE3B73');
        $this->addSql('ALTER TABLE event_like DROP FOREIGN KEY FK_B3A80C1871F7E88B');
        $this->addSql('ALTER TABLE event_like DROP FOREIGN KEY FK_B3A80C18A76ED395');
        $this->addSql('ALTER TABLE event_participation DROP FOREIGN KEY FK_8F0C52E371F7E88B');
        $this->addSql('ALTER TABLE event_participation DROP FOREIGN KEY FK_8F0C52E3A76ED395');
        $this->addSql('ALTER TABLE formation DROP FOREIGN KEY FK_404021BF156BE243');
        $this->addSql('ALTER TABLE interviews DROP FOREIGN KEY FK_3A7526823E030ACD');
        $this->addSql('ALTER TABLE meets DROP FOREIGN KEY FK_673BD66F55D69D95');
        $this->addSql('ALTER TABLE notifications DROP FOREIGN KEY FK_6000B0D3A76ED395');
        $this->addSql('ALTER TABLE offers DROP FOREIGN KEY FK_DA460427156BE243');
        $this->addSql('ALTER TABLE profiles DROP FOREIGN KEY FK_8B308530A76ED395');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE60984F51');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494E853CD175');
        $this->addSql('ALTER TABLE quiz DROP FOREIGN KEY FK_A412FA925200282E');
        $this->addSql('ALTER TABLE seance DROP FOREIGN KEY FK_DF7DFD0E5200282E');
        $this->addSql('ALTER TABLE support_tickets DROP FOREIGN KEY FK_E9739508A76ED395');
        $this->addSql('ALTER TABLE sync_messages DROP FOREIGN KEY FK_9A10A0B8FA50C422');
        $this->addSql('ALTER TABLE sync_messages DROP FOREIGN KEY FK_9A10A0B8F624B39D');
        $this->addSql('ALTER TABLE syncs DROP FOREIGN KEY FK_ABF27CDCF624B39D');
        $this->addSql('ALTER TABLE syncs DROP FOREIGN KEY FK_ABF27CDCCD53EDB6');
        $this->addSql('ALTER TABLE ticket_replies DROP FOREIGN KEY FK_ACCC3E78700047D2');
        $this->addSql('ALTER TABLE ticket_replies DROP FOREIGN KEY FK_ACCC3E78A76ED395');
        $this->addSql('DROP TABLE activities');
        $this->addSql('DROP TABLE applications');
        $this->addSql('DROP TABLE bookmarks');
        $this->addSql('DROP TABLE choix');
        $this->addSql('DROP TABLE event');
        $this->addSql('DROP TABLE event_comment');
        $this->addSql('DROP TABLE event_feedback');
        $this->addSql('DROP TABLE event_like');
        $this->addSql('DROP TABLE event_participation');
        $this->addSql('DROP TABLE formation');
        $this->addSql('DROP TABLE interviews');
        $this->addSql('DROP TABLE meets');
        $this->addSql('DROP TABLE notifications');
        $this->addSql('DROP TABLE offers');
        $this->addSql('DROP TABLE profiles');
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE question');
        $this->addSql('DROP TABLE quiz');
        $this->addSql('DROP TABLE seance');
        $this->addSql('DROP TABLE support_tickets');
        $this->addSql('DROP TABLE sync_messages');
        $this->addSql('DROP TABLE syncs');
        $this->addSql('DROP TABLE ticket_replies');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
