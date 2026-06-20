-- CivicLedger "The Problem Solver" - Complete Database Schema
-- Version 2.0 - With Nepal Startup Governance Rules

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+05:45";

-- Create database
CREATE DATABASE IF NOT EXISTS `elite4_nepal` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `elite4_nepal`;

-- ===============================================
-- USERS TABLE (Updated with Trust Score)
-- ===============================================
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) UNIQUE NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('citizen', 'student', 'sponsor', 'mentor', 'admin', 'moderator') NOT NULL DEFAULT 'citizen',
  `bio` TEXT DEFAULT NULL,
  `skills` TEXT DEFAULT NULL,
  `profile_photo` VARCHAR(255) DEFAULT NULL,
  `date_of_birth` DATE DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `trust_score` INT DEFAULT 100,
  `projects_completed` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- USER SUBSCRIPTIONS
-- ===============================================
CREATE TABLE `user_subscriptions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `plan` ENUM('free', 'plus', 'premium') NOT NULL DEFAULT 'free',
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `mentor_messages_used` INT DEFAULT 0,
  `mentor_messages_reset_at` DATE DEFAULT NULL,
  `status` ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- TRUST SCORE LOG (Rule #1: Gamified Reputation)
-- ===============================================
CREATE TABLE `trust_score_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `action_type` VARCHAR(50) NOT NULL,
  `points_change` INT NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `related_id` INT DEFAULT NULL,
  `related_type` VARCHAR(50) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- STARTUP VERIFICATIONS (Rule #5: Nepal Startup Verification)
-- ===============================================
CREATE TABLE `startup_verifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `company_name` VARCHAR(200) NOT NULL,
  `registration_number` VARCHAR(100) DEFAULT NULL,
  `pan_vat_number` VARCHAR(50) DEFAULT NULL,
  `founder_name` VARCHAR(100) NOT NULL,
  `founder_id_number` VARCHAR(50) DEFAULT NULL,
  `company_registration_doc` VARCHAR(255) DEFAULT NULL,
  `is_verified` TINYINT(1) DEFAULT 0,
  `verified_at` TIMESTAMP NULL DEFAULT NULL,
  `verified_by` INT DEFAULT NULL,
  `escrow_agreement_accepted` TINYINT(1) DEFAULT 0,
  `badge_level` ENUM('none', 'basic', 'verified', 'premium') DEFAULT 'none',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`verified_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- PROBLEMS TABLE (Updated with Content Approval)
-- ===============================================
CREATE TABLE `problems` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NOT NULL,
  `location` VARCHAR(255) DEFAULT NULL,
  `photo` VARCHAR(255) DEFAULT NULL,
  `voice_note` TEXT DEFAULT NULL,
  `category` ENUM('Waste', 'Road', 'Health', 'Water', 'Other') DEFAULT 'Other',
  `urgency` ENUM('High', 'Medium', 'Low') DEFAULT 'Medium',
  `status` ENUM('open', 'in_progress', 'solved') DEFAULT 'open',
  `upvotes` INT DEFAULT 0,
  `solved_by` INT DEFAULT NULL,
  `solved_at` TIMESTAMP NULL DEFAULT NULL,
  `sdg_impact_score` INT DEFAULT 0,
  `local_impact_category` VARCHAR(50) DEFAULT NULL,
  `is_approved` INT DEFAULT 0 COMMENT '0=pending, 1=approved, -1=rejected',
  `approved_by` INT DEFAULT NULL,
  `approved_at` TIMESTAMP NULL DEFAULT NULL,
  `rejection_reason` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`solved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- TEAMS TABLE (Updated with Gold Badge & PoP)
-- ===============================================
CREATE TABLE `teams` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `leader_id` INT NOT NULL,
  `is_public` TINYINT(1) DEFAULT 1,
  `required_skills` TEXT DEFAULT NULL,
  `problem_id` INT DEFAULT NULL,
  `mentor_id` INT DEFAULT NULL,
  `members` JSON DEFAULT NULL,
  `rank_points` INT DEFAULT 0,
  `status` ENUM('active', 'completed', 'archived', 'inactive') DEFAULT 'active',
  `gold_badge` TINYINT(1) DEFAULT 0,
  `projects_completed` INT DEFAULT 0,
  `last_progress_update` TIMESTAMP NULL DEFAULT NULL,
  `is_inactive` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`leader_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`mentor_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- JOIN REQUESTS TABLE
-- ===============================================
CREATE TABLE `join_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `team_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `proposal` TEXT NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `reviewed_by` INT DEFAULT NULL,
  `reviewed_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- TEAM MILESTONES / PROGRESS
-- ===============================================
CREATE TABLE `team_milestones` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `team_id` INT NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
  `due_date` DATE DEFAULT NULL,
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- PROOF OF PROGRESS (Rule #2: PoP System)
-- ===============================================
CREATE TABLE `progress_updates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `team_id` INT NOT NULL,
  `milestone_id` INT DEFAULT NULL,
  `update_type` ENUM('commit_link', 'photo', 'mentor_signoff', 'document', 'other') NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `link_url` VARCHAR(500) DEFAULT NULL,
  `photo_url` VARCHAR(255) DEFAULT NULL,
  `mentor_approved` TINYINT(1) DEFAULT 0,
  `mentor_approved_by` INT DEFAULT NULL,
  `mentor_approved_at` TIMESTAMP NULL DEFAULT NULL,
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`milestone_id`) REFERENCES `team_milestones`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`mentor_approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- IP SUBMISSIONS (Rule #6: Student IP Protection)
-- ===============================================
CREATE TABLE `ip_submissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `team_id` INT DEFAULT NULL,
  `user_id` INT NOT NULL,
  `submission_type` ENUM('solution', 'prototype', 'idea', 'document') NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `file_url` VARCHAR(255) DEFAULT NULL,
  `ip_hash` VARCHAR(64) DEFAULT NULL,
  `is_exclusive` TINYINT(1) DEFAULT 0,
  `ip_purchased_by` INT DEFAULT NULL,
  `purchase_amount` DECIMAL(10,2) DEFAULT NULL,
  `purchase_date` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`ip_purchased_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- SOLUTIONS TABLE (Updated with Likes/Dislikes & Approval)
-- ===============================================
CREATE TABLE `solutions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `problem_id` INT DEFAULT NULL,
  `challenge_id` INT DEFAULT NULL,
  `team_id` INT DEFAULT NULL,
  `user_id` INT NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NOT NULL,
  `budget_estimate` DECIMAL(10,2) DEFAULT 0,
  `implementation_plan` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'approved', 'rejected', 'rewarded', 'disputed') DEFAULT 'pending',
  `mentor_feedback` TEXT DEFAULT NULL,
  `reward_gross` DECIMAL(10,2) DEFAULT 0,
  `reward_commission` DECIMAL(10,2) DEFAULT 0,
  `reward_net` DECIMAL(10,2) DEFAULT 0,
  `likes` INT DEFAULT 0,
  `dislikes` INT DEFAULT 0,
  `is_approved` INT DEFAULT 0 COMMENT '0=pending, 1=approved, -1=rejected',
  `approved_by` INT DEFAULT NULL,
  `approved_at` TIMESTAMP NULL DEFAULT NULL,
  `rejection_reason` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`problem_id`) REFERENCES `problems`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- CHALLENGES TABLE (Updated with Escrow & Impact)
-- ===============================================
CREATE TABLE `challenges` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sponsor_id` INT NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NOT NULL,
  `reward_amount` DECIMAL(10,2) NOT NULL,
  `escrow_deposit` DECIMAL(10,2) DEFAULT 0,
  `escrow_status` ENUM('none', 'pending', 'deposited', 'released', 'refunded') DEFAULT 'none',
  `category` VARCHAR(50) DEFAULT NULL,
  `sdg_focus` INT DEFAULT NULL,
  `local_impact_category` VARCHAR(50) DEFAULT NULL,
  `requires_mentor` TINYINT(1) DEFAULT 0,
  `assigned_mentor_id` INT DEFAULT NULL,
  `deadline` DATE DEFAULT NULL,
  `status` ENUM('open', 'closed', 'rewarded', 'disputed') DEFAULT 'open',
  `first_look_expires` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`sponsor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_mentor_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- SPONSORSHIPS TABLE
-- ===============================================
CREATE TABLE `sponsorships` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sponsor_id` INT NOT NULL,
  `recipient_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'approved', 'disbursed', 'rejected') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`sponsor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`recipient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- SPONSOR ACTIVITY LOG (Updated with First-Look)
-- ===============================================
CREATE TABLE `sponsor_activity_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sponsor_id` INT NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`sponsor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- FIRST-LOOK ACCESS LOG (Rule #3: First-Look Rights)
-- ===============================================
CREATE TABLE `first_look_access` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sponsor_id` INT NOT NULL,
  `challenge_id` INT DEFAULT NULL,
  `team_id` INT DEFAULT NULL,
  `problem_id` INT DEFAULT NULL,
  `access_type` ENUM('challenge', 'team', 'problem') NOT NULL,
  `accessed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NULL DEFAULT NULL,
  FOREIGN KEY (`sponsor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- DISPUTES (Rule #4: Stakeholder Dispute Resolution)
-- ===============================================
CREATE TABLE `disputes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `challenge_id` INT NOT NULL,
  `solution_id` INT DEFAULT NULL,
  `team_id` INT DEFAULT NULL,
  `raised_by` INT NOT NULL,
  `against_type` ENUM('sponsor', 'team') NOT NULL,
  `dispute_type` ENUM('payment_refused', 'quality_dispute', 'timeline_dispute', 'ip_dispute', 'other') NOT NULL,
  `description` TEXT NOT NULL,
  `evidence_url` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('open', 'under_review', 'resolved_sponsor', 'resolved_team', 'rejected') DEFAULT 'open',
  `assigned_arbitrator_id` INT DEFAULT NULL,
  `arbitrator_notes` TEXT DEFAULT NULL,
  `final_decision` TEXT DEFAULT NULL,
  `funds_action` ENUM('release_to_team', 'refund_sponsor', 'split', 'no_action') DEFAULT 'no_action',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` TIMESTAMP NULL DEFAULT NULL,
  FOREIGN KEY (`challenge_id`) REFERENCES `challenges`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`solution_id`) REFERENCES `solutions`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`raised_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_arbitrator_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- CHAT GROUPS TABLE
-- ===============================================
CREATE TABLE `chat_groups` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) DEFAULT NULL,
  `type` ENUM('team', 'mentor', 'sponsor_admin', 'direct') NOT NULL,
  `team_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- CHAT MESSAGES TABLE
-- ===============================================
CREATE TABLE `chat_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `group_id` INT DEFAULT NULL,
  `sender_id` INT NOT NULL,
  `receiver_id` INT DEFAULT NULL,
  `message` TEXT NOT NULL,
  `is_deleted` TINYINT(1) DEFAULT 0,
  `deleted_by` INT DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`deleted_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_group` (`group_id`),
  INDEX `idx_users` (`sender_id`, `receiver_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- MICRO GIGS TABLE (Updated with Trust Score Requirement)
-- ===============================================
CREATE TABLE `micro_gigs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `citizen_id` INT NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NOT NULL,
  `budget` DECIMAL(10,2) NOT NULL,
  `category` VARCHAR(50) DEFAULT NULL,
  `min_trust_score` INT DEFAULT 0,
  `status` ENUM('open', 'assigned', 'completed') DEFAULT 'open',
  `assigned_to` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`citizen_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- GIG APPLICATIONS TABLE
-- ===============================================
CREATE TABLE `gig_applications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `gig_id` INT NOT NULL,
  `student_id` INT NOT NULL,
  `proposal` TEXT NOT NULL,
  `status` ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`gig_id`) REFERENCES `micro_gigs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- MENTOR ASSIGNMENTS TABLE
-- ===============================================
CREATE TABLE `mentor_assignments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `team_id` INT NOT NULL,
  `mentor_id` INT NOT NULL,
  `assigned_by` INT NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`mentor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- TEAM MEMBERS TABLE
-- ===============================================
CREATE TABLE `team_members` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `team_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `role` ENUM('member', 'co-leader') DEFAULT 'member',
  `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_team_member` (`team_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- MODERATOR ASSIGNMENTS TABLE
-- ===============================================
CREATE TABLE `moderator_assignments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `team_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `assigned_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_mod` (`team_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- ADMIN MESSAGES TABLE
-- ===============================================
CREATE TABLE `admin_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sender_id` INT NOT NULL,
  `receiver_id` INT DEFAULT NULL,
  `sender_role` ENUM('sponsor','admin') DEFAULT 'sponsor',
  `subject` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `reply_message` TEXT DEFAULT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `replied_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- SUCCESS STORIES TABLE
-- ===============================================
CREATE TABLE `success_stories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `story` TEXT NOT NULL,
  `author_name` VARCHAR(100) NOT NULL,
  `author_role` VARCHAR(100) DEFAULT '',
  `location` VARCHAR(100) DEFAULT '',
  `image_url` VARCHAR(500) DEFAULT '',
  `reward_amount` DECIMAL(10,2) DEFAULT 0,
  `impact_metric` VARCHAR(255) DEFAULT '',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- PLATFORM SETTINGS TABLE
-- ===============================================
CREATE TABLE `platform_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(50) UNIQUE NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- OFFENSIVE WORDS FILTER TABLE (Rule #11)
-- ===============================================
CREATE TABLE `offensive_words` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `word` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- COMPLIANCE VIOLATIONS (Rule #11: Nepal Compliance)
-- ===============================================
CREATE TABLE `compliance_violations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `violation_type` ENUM('fraud', 'gambling', 'illegal_finance', 'academic_cheating', 'privacy_violation', 'hate_speech', 'copyright_infringement', 'other') NOT NULL,
  `description` TEXT NOT NULL,
  `evidence_url` VARCHAR(255) DEFAULT NULL,
  `severity` ENUM('warning', 'moderate', 'severe') DEFAULT 'warning',
  `status` ENUM('reported', 'investigating', 'confirmed', 'dismissed') DEFAULT 'reported',
  `reviewed_by` INT DEFAULT NULL,
  `reviewed_at` TIMESTAMP NULL DEFAULT NULL,
  `trust_score_penalty` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- SOLUTION LIKES/DISLIKES (Trust System)
-- ===============================================
CREATE TABLE `solution_likes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `solution_id` INT NOT NULL,
  `type` ENUM('like', 'dislike') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`solution_id`) REFERENCES `solutions`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_vote` (`user_id`, `solution_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- USER WARNINGS (Admin Management)
-- ===============================================
CREATE TABLE `user_warnings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `sent_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`sent_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- INSERT SAMPLE DATA
-- ===============================================

-- Insert sample users (password: 123456)
INSERT INTO `users` (`name`, `email`, `phone`, `password`, `role`, `bio`, `skills`, `trust_score`, `projects_completed`) VALUES
('Ram Bahadur', 'citizen@elite4.com', '+977-9800000001', '$2y$10$Xd7Z6V8c9K0rOq5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'citizen', 'Concerned citizen from Kathmandu passionate about community development.', 'Community engagement, Problem identification', 100, 0),
('Sita Kumari', 'student@elite4.com', '+977-9800000002', '$2y$10$Xd7Z6V8c9K0rOq5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Computer Science student at TU. Eager to solve real-world problems.', 'Programming, Web Development, UI/UX Design', 95, 2),
('TechVenture Nepal', 'sponsor@elite4.com', '+977-9800000003', '$2y$10$Xd7Z6V8c9K0rOq5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'sponsor', 'Tech startup focused on sustainable solutions for urban problems in Nepal.', 'Funding, Technology, Mentorship', 100, 0),
('Dr. Arun Sharma', 'mentor@elite4.com', '+977-9800000004', '$2y$10$Xd7Z6V8c9K0rOq5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mentor', 'Experienced entrepreneur with 15+ years in startup mentorship.', 'Business Strategy, Innovation, Marketing', 100, 0),
('Admin User', 'admin@elite4.com', '+977-9800000005', '$2y$10$Xd7Z6V8c9K0rOq5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Platform administrator for CivicLedger.', 'Management, System Administration', 100, 0),
('Gita Tamang', 'gita@elite4.com', '+977-9800000006', '$2y$10$Xd7Z6V8c9K0rOq5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Environmental engineering student passionate about waste management.', 'Research, Analysis, Field Work', 88, 1);

-- Insert startup verification for sponsor
INSERT INTO `startup_verifications` (`user_id`, `company_name`, `registration_number`, `pan_vat_number`, `founder_name`, `is_verified`, `verified_at`, `escrow_agreement_accepted`, `badge_level`) VALUES
(3, 'TechVenture Nepal Pvt. Ltd.', 'REG-2020-1234', 'PAN-987654321', 'Raj Kumar', 1, NOW(), 1, 'verified');

-- Insert subscriptions
INSERT INTO `user_subscriptions` (`user_id`, `plan`, `start_date`, `end_date`, `mentor_messages_used`, `mentor_messages_reset_at`) VALUES
(2, 'free', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1, CURDATE()),
(3, 'premium', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 0, CURDATE()),
(4, 'premium', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 0, CURDATE()),
(5, 'premium', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 0, CURDATE()),
(6, 'plus', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 0, CURDATE());

-- Insert sample problems
INSERT INTO `problems` (`user_id`, `title`, `description`, `location`, `category`, `urgency`, `status`, `upvotes`, `local_impact_category`) VALUES
(1, 'Garbage collection crisis in Kapan area', 'Waste management has been terrible in our area for the past 2 weeks. Garbage bins are overflowing and creating serious health hazards.', 'Kapan Village, Kathmandu', 'Waste', 'High', 'open', 15, 'Environment Protection'),
(1, 'Dangerous pothole on Ring Road', 'Large pothole near the bus park has caused multiple motorcycle accidents. Someone died last week.', 'Ring Road, Kathmandu', 'Road', 'High', 'open', 23, 'Digital Transformation'),
(1, 'Contaminated water supply in Baneshwor', 'Water from taps has strange color and smell for past week. Several residents including children have fallen sick.', 'Baneshwor, Kathmandu', 'Water', 'High', 'open', 18, 'Health Improvement');

-- Insert sample team with progress tracking
INSERT INTO `teams` (`name`, `description`, `leader_id`, `is_public`, `required_skills`, `members`, `rank_points`, `mentor_id`, `gold_badge`, `projects_completed`, `last_progress_update`) VALUES
('EcoWarriors', 'Team focused on environmental solutions using technology and community engagement.', 2, 1, 'Programming, Research, Field Work', '[2, 6]', 45, 4, 0, 1, NOW());

-- Insert sample challenge with escrow
INSERT INTO `challenges` (`sponsor_id`, `title`, `description`, `reward_amount`, `escrow_deposit`, `escrow_status`, `category`, `sdg_focus`, `local_impact_category`, `requires_mentor`, `deadline`, `first_look_expires`) VALUES
(3, 'Smart Waste Collection System', 'Create an IoT-based smart waste monitoring system that optimizes collection routes.', 75000.00, 7500.00, 'deposited', 'Technology', 11, 'Environment Protection', 1, DATE_ADD(CURDATE(), INTERVAL 60 DAY), DATE_ADD(NOW(), INTERVAL 2 DAY));

-- Insert sample solution
INSERT INTO `solutions` (`problem_id`, `team_id`, `user_id`, `title`, `description`, `budget_estimate`, `status`) VALUES
(2, 1, 2, 'Smart Road Monitor System', 'Install solar-powered sensors at pothole locations that send real-time data to municipal systems.', 25000.00, 'pending');

-- Insert mentor assignments
INSERT INTO `mentor_assignments` (`team_id`, `mentor_id`, `assigned_by`, `notes`) VALUES
(1, 4, 5, 'Expert in environmental tech and startup mentorship');

-- Insert team members
INSERT INTO `team_members` (`team_id`, `user_id`, `role`) VALUES
(1, 2, 'member'),
(1, 6, 'member');

-- Insert team milestones
INSERT INTO `team_milestones` (`team_id`, `title`, `description`, `status`, `due_date`, `completed_at`) VALUES
(1, 'Research Phase', 'Research current waste management systems', 'completed', DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_SUB(CURDATE(), INTERVAL 5 DAY)),
(1, 'Prototype Development', 'Build IoT sensor prototype', 'in_progress', DATE_ADD(CURDATE(), INTERVAL 15 DAY), NULL),
(1, 'Field Testing', 'Test prototype in selected areas', 'pending', DATE_ADD(CURDATE(), INTERVAL 30 DAY), NULL);

-- Insert progress update
INSERT INTO `progress_updates` (`team_id`, `milestone_id`, `update_type`, `title`, `description`, `link_url`, `mentor_approved`, `created_by`) VALUES
(1, 1, 'commit_link', 'GitHub Repository Setup', 'Initial repository with research documentation', 'https://github.com/ecowarriors/research', 1, 2);

-- Insert success stories
INSERT INTO `success_stories` (`title`, `story`, `author_name`, `author_role`, `location`, `reward_amount`, `impact_metric`, `is_active`) VALUES
('Team EcoTech Wins Innovation Award', 'Our team developed an AI-powered waste classification system. With CivicLedger sponsorship, we deployed sensors in 50 community bins.', 'Priya Sharma', 'Team Leader, EcoTech Nepal', 'Kathmandu, Nepal', 150000.00, '87% accuracy, 60% waste reduction, 50 bins deployed', 1),
('Green Water Initiative Reaches 2000+ Households', 'Supported by sponsors through CivicLedger, our team designed low-cost iron removal filters.', 'Raj Kumar', 'Project Coordinator, Water Guardians', 'Pokhara, Nepal', 80000.00, 'Clean water for 2,000+ households, 200 trained volunteers', 1),
('Youth Coding Initiative Trains 500+ Students', 'Partnered with TechVenture Nepal to create coding bootcamps in 5 underserved districts.', 'Sita Tamang', 'Founder, Code for Nepal', 'Multiple Districts, Nepal', 120000.00, '500+ students trained, 12 apps built, 5 districts covered', 1);

-- Insert platform settings
INSERT INTO `platform_settings` (`setting_key`, `setting_value`) VALUES
('commission_percent', '0'),
('platform_name', 'CivicLedger'),
('platform_tagline', 'The Problem Solver'),
('support_email', 'support@elite4nepal.com'),
('min_trust_score_for_high_gigs', '60'),
('first_look_hours', '48'),
('escrow_deposit_percent', '10'),
('pop_update_interval_days', '14'),
('gold_badge_projects_required', '3');

-- Insert offensive words
INSERT INTO `offensive_words` (`word`) VALUES
('spam'), ('hate'), ('abuse');

-- Insert trust score logs
INSERT INTO `trust_score_logs` (`user_id`, `action_type`, `points_change`, `description`, `related_id`, `related_type`) VALUES
(2, 'milestone_completed', 5, 'Completed Research Phase milestone', 1, 'milestone'),
(6, 'project_participated', 3, 'Joined EcoWarriors team', 1, 'team'),
(2, 'upvote_received', 2, 'Problem received upvote', 2, 'problem');