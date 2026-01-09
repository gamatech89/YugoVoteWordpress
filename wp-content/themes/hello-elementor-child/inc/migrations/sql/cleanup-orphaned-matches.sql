-- COMPLETE TOURNAMENT CLEANUP - Removes ALL tournament data
-- This will DELETE EVERYTHING related to tournaments
-- Run this in phpMyAdmin SQL tab

-- Step 1: Delete all votes for tournament matches
DELETE v FROM wp_voting_list_votes v
INNER JOIN wp_postmeta pm ON v.voting_list_id = pm.post_id
WHERE pm.meta_key = '_is_tournament_match' AND pm.meta_value = '1';

-- Step 2: Delete all meta data for tournament matches
DELETE pm FROM wp_postmeta pm
INNER JOIN wp_posts p ON pm.post_id = p.ID
INNER JOIN wp_postmeta pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_is_tournament_match'
WHERE p.post_type = 'voting_list' AND pm2.meta_value = '1';

-- Step 3: Delete all tournament match posts
DELETE p FROM wp_posts p
INNER JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_is_tournament_match'
WHERE p.post_type = 'voting_list' AND pm.meta_value = '1';

-- Step 4: Delete all tournament meta data
DELETE FROM wp_postmeta WHERE post_id IN (
    SELECT ID FROM wp_posts WHERE post_type = 'yuv_tournament'
);

-- Step 5: Delete all tournament posts
DELETE FROM wp_posts WHERE post_type = 'yuv_tournament';

-- Step 6: Verify cleanup (run this to check if everything is gone)
SELECT 'Remaining tournaments' as type, COUNT(*) as count FROM wp_posts WHERE post_type = 'yuv_tournament'
UNION ALL
SELECT 'Remaining tournament matches' as type, COUNT(*) as count FROM wp_posts p 
INNER JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_is_tournament_match'
WHERE p.post_type = 'voting_list';

