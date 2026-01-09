-- SQL Script to Update Existing Tournament Matches
-- Run this in your WordPress database to fix existing tournament matches

-- 1. Update _yuv_match_completed from empty string to '0' for incomplete matches
UPDATE wp_postmeta 
SET meta_value = '0' 
WHERE meta_key = '_yuv_match_completed' 
AND meta_value = '';

-- 2. Add _is_tournament_match flag to all existing tournament matches
-- This will mark all voting_list posts that have a tournament_id as tournament matches
INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
SELECT DISTINCT post_id, '_is_tournament_match', '1'
FROM wp_postmeta
WHERE meta_key = '_yuv_tournament_id'
AND post_id NOT IN (
    SELECT post_id 
    FROM wp_postmeta 
    WHERE meta_key = '_is_tournament_match'
);

-- 3. Verify the results (optional - for checking)
-- SELECT p.ID, p.post_title, 
--        pm1.meta_value as is_tournament,
--        pm2.meta_value as completed,
--        pm3.meta_value as tournament_id
-- FROM wp_posts p
-- LEFT JOIN wp_postmeta pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_is_tournament_match'
-- LEFT JOIN wp_postmeta pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_yuv_match_completed'
-- LEFT JOIN wp_postmeta pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_yuv_tournament_id'
-- WHERE p.post_type = 'voting_list'
-- AND pm3.meta_value IS NOT NULL;
