SET @dbname = 'pidev';

-- Add missing indexes for FK support
-- event tables
SELECT COUNT(*) INTO @cnt FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='event_comment' AND INDEX_NAME='idx_ec_event';
SET @sql = IF(@cnt=0, 'CREATE INDEX idx_ec_event ON event_comment(event_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @cnt FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='event_comment' AND INDEX_NAME='idx_ec_user';
SET @sql = IF(@cnt=0, 'CREATE INDEX idx_ec_user ON event_comment(user_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @cnt FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='event_like' AND INDEX_NAME='idx_el_event';
SET @sql = IF(@cnt=0, 'CREATE INDEX idx_el_event ON event_like(event_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @cnt FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='event_like' AND INDEX_NAME='idx_el_user';
SET @sql = IF(@cnt=0, 'CREATE INDEX idx_el_user ON event_like(user_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @cnt FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='event_participation' AND INDEX_NAME='idx_ep_event';
SET @sql = IF(@cnt=0, 'CREATE INDEX idx_ep_event ON event_participation(event_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @cnt FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='event_participation' AND INDEX_NAME='idx_ep_user';
SET @sql = IF(@cnt=0, 'CREATE INDEX idx_ep_user ON event_participation(user_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @cnt FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='event' AND INDEX_NAME='idx_ev_org';
SET @sql = IF(@cnt=0, 'CREATE INDEX idx_ev_org ON event(organizer_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- applications
SELECT COUNT(*) INTO @cnt FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='applications' AND INDEX_NAME='idx_app_user';
SET @sql = IF(@cnt=0, 'CREATE INDEX idx_app_user ON applications(user_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- bookmarks
SELECT COUNT(*) INTO @cnt FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='bookmarks' AND INDEX_NAME='idx_bm_user';
SET @sql = IF(@cnt=0, 'CREATE INDEX idx_bm_user ON bookmarks(user_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @cnt FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='bookmarks' AND INDEX_NAME='idx_bm_offer';
SET @sql = IF(@cnt=0, 'CREATE INDEX idx_bm_offer ON bookmarks(offer_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- support_tickets
SELECT COUNT(*) INTO @cnt FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='support_tickets' AND INDEX_NAME='idx_st_user';
SET @sql = IF(@cnt=0, 'CREATE INDEX idx_st_user ON support_tickets(user_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ticket_replies
SELECT COUNT(*) INTO @cnt FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='ticket_replies' AND INDEX_NAME='idx_tr_ticket';
SET @sql = IF(@cnt=0, 'CREATE INDEX idx_tr_ticket ON ticket_replies(ticket_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
