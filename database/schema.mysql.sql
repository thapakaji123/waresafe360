SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS user_achievements, achievements, hazard_responses, attempt_scene_progress, training_attempts, question_options, questions, hazards, scenes, training_modules, users, roles;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE training_modules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) NOT NULL UNIQUE,
    title VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    objectives TEXT NOT NULL,
    difficulty VARCHAR(30) NOT NULL DEFAULT 'Foundation',
    min_hazard_percent TINYINT UNSIGNED NOT NULL DEFAULT 75,
    min_accuracy_percent TINYINT UNSIGNED NOT NULL DEFAULT 70,
    scoring_profile JSON NOT NULL,
    content_version INT UNSIGNED NOT NULL DEFAULT 1,
    status ENUM('draft','active','inactive') NOT NULL DEFAULT 'draft',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CHECK (min_hazard_percent <= 100),
    CHECK (min_accuracy_percent <= 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE scenes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(30) NOT NULL,
    title VARCHAR(160) NOT NULL,
    description TEXT NOT NULL,
    sequence_no INT UNSIGNED NOT NULL,
    panorama_path VARCHAR(255) NOT NULL,
    initial_yaw DOUBLE NOT NULL DEFAULT 0,
    initial_pitch DOUBLE NOT NULL DEFAULT 0,
    initial_fov DOUBLE NOT NULL DEFAULT 1.35,
    is_tutorial BOOLEAN NOT NULL DEFAULT FALSE,
    time_limit_seconds INT UNSIGNED NULL,
    status ENUM('draft','active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_scene_code (module_id, code),
    UNIQUE KEY uq_scene_sequence (module_id, sequence_no),
    CONSTRAINT fk_scenes_module FOREIGN KEY (module_id) REFERENCES training_modules(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hazards (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    scene_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(30) NOT NULL,
    title VARCHAR(180) NOT NULL,
    category VARCHAR(100) NOT NULL,
    severity ENUM('low','medium','high','critical') NOT NULL,
    yaw DOUBLE NOT NULL,
    pitch DOUBLE NOT NULL,
    hint TEXT NULL,
    is_assessed BOOLEAN NOT NULL DEFAULT TRUE,
    status ENUM('draft','active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_hazard_code (scene_id, code),
    CONSTRAINT fk_hazards_scene FOREIGN KEY (scene_id) REFERENCES scenes(id),
    CHECK (yaw >= -3.141593 AND yaw <= 3.141593),
    CHECK (pitch >= -1.570797 AND pitch <= 1.570797)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE questions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hazard_id BIGINT UNSIGNED NOT NULL UNIQUE,
    question_text TEXT NOT NULL,
    explanation TEXT NOT NULL,
    status ENUM('draft','active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_questions_hazard FOREIGN KEY (hazard_id) REFERENCES hazards(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE question_options (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id BIGINT UNSIGNED NOT NULL,
    option_text TEXT NOT NULL,
    is_correct BOOLEAN NOT NULL DEFAULT FALSE,
    sequence_no INT UNSIGNED NOT NULL,
    UNIQUE KEY uq_option_sequence (question_id, sequence_no),
    CONSTRAINT fk_options_question FOREIGN KEY (question_id) REFERENCES questions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE training_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    module_id BIGINT UNSIGNED NOT NULL,
    content_version INT UNSIGNED NOT NULL,
    content_snapshot JSON NOT NULL,
    state ENUM('created','in_progress','completed','timed_out','abandoned') NOT NULL DEFAULT 'in_progress',
    current_scene_id BIGINT UNSIGNED NULL,
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    elapsed_seconds INT UNSIGNED NOT NULL DEFAULT 0,
    score INT UNSIGNED NOT NULL DEFAULT 0,
    hazard_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    accuracy_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    passed BOOLEAN NULL,
    leaderboard_eligible BOOLEAN NOT NULL DEFAULT TRUE,
    INDEX idx_attempts_user (user_id, started_at),
    INDEX idx_attempts_module_state (module_id, state, completed_at),
    CONSTRAINT fk_attempts_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_attempts_module FOREIGN KEY (module_id) REFERENCES training_modules(id),
    CONSTRAINT fk_attempts_current_scene FOREIGN KEY (current_scene_id) REFERENCES scenes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE attempt_scene_progress (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attempt_id BIGINT UNSIGNED NOT NULL,
    scene_id BIGINT UNSIGNED NOT NULL,
    state ENUM('not_started','in_progress','completed','timed_out') NOT NULL DEFAULT 'not_started',
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    elapsed_seconds INT UNSIGNED NOT NULL DEFAULT 0,
    hazards_found INT UNSIGNED NOT NULL DEFAULT 0,
    correct_answers INT UNSIGNED NOT NULL DEFAULT 0,
    score INT UNSIGNED NOT NULL DEFAULT 0,
    UNIQUE KEY uq_attempt_scene (attempt_id, scene_id),
    CONSTRAINT fk_progress_attempt FOREIGN KEY (attempt_id) REFERENCES training_attempts(id),
    CONSTRAINT fk_progress_scene FOREIGN KEY (scene_id) REFERENCES scenes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hazard_responses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attempt_id BIGINT UNSIGNED NOT NULL,
    scene_id BIGINT UNSIGNED NOT NULL,
    hazard_id BIGINT UNSIGNED NOT NULL,
    question_id BIGINT UNSIGNED NOT NULL,
    option_id BIGINT UNSIGNED NOT NULL,
    is_correct BOOLEAN NOT NULL,
    discovery_points INT NOT NULL DEFAULT 0,
    answer_points INT NOT NULL DEFAULT 0,
    total_points INT NOT NULL DEFAULT 0,
    client_event_id VARCHAR(80) NOT NULL,
    answered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_response_hazard (attempt_id, hazard_id),
    UNIQUE KEY uq_response_event (attempt_id, client_event_id),
    INDEX idx_responses_attempt (attempt_id),
    CONSTRAINT fk_responses_attempt FOREIGN KEY (attempt_id) REFERENCES training_attempts(id),
    CONSTRAINT fk_responses_scene FOREIGN KEY (scene_id) REFERENCES scenes(id),
    CONSTRAINT fk_responses_hazard FOREIGN KEY (hazard_id) REFERENCES hazards(id),
    CONSTRAINT fk_responses_question FOREIGN KEY (question_id) REFERENCES questions(id),
    CONSTRAINT fk_responses_option FOREIGN KEY (option_id) REFERENCES question_options(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE achievements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    description TEXT NOT NULL,
    criteria_code VARCHAR(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_achievements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    achievement_id BIGINT UNSIGNED NOT NULL,
    attempt_id BIGINT UNSIGNED NOT NULL,
    awarded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_achievement_attempt (user_id, achievement_id, attempt_id),
    CONSTRAINT fk_user_achievements_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_user_achievements_achievement FOREIGN KEY (achievement_id) REFERENCES achievements(id),
    CONSTRAINT fk_user_achievements_attempt FOREIGN KEY (attempt_id) REFERENCES training_attempts(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

