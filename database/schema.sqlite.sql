PRAGMA foreign_keys = OFF;
DROP TABLE IF EXISTS user_achievements;
DROP TABLE IF EXISTS achievements;
DROP TABLE IF EXISTS hazard_responses;
DROP TABLE IF EXISTS attempt_scene_progress;
DROP TABLE IF EXISTS training_attempts;
DROP TABLE IF EXISTS question_options;
DROP TABLE IF EXISTS questions;
DROP TABLE IF EXISTS hazards;
DROP TABLE IF EXISTS scenes;
DROP TABLE IF EXISTS training_modules;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;
PRAGMA foreign_keys = ON;

CREATE TABLE roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(30) NOT NULL UNIQUE
);

CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    role_id INTEGER NOT NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

CREATE TABLE training_modules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug VARCHAR(100) NOT NULL UNIQUE,
    title VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    objectives TEXT NOT NULL,
    difficulty VARCHAR(30) NOT NULL DEFAULT 'Foundation',
    min_hazard_percent INTEGER NOT NULL DEFAULT 75 CHECK (min_hazard_percent BETWEEN 0 AND 100),
    min_accuracy_percent INTEGER NOT NULL DEFAULT 70 CHECK (min_accuracy_percent BETWEEN 0 AND 100),
    scoring_profile TEXT NOT NULL,
    content_version INTEGER NOT NULL DEFAULT 1,
    status VARCHAR(20) NOT NULL DEFAULT 'draft' CHECK (status IN ('draft', 'active', 'inactive')),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE scenes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    module_id INTEGER NOT NULL,
    code VARCHAR(30) NOT NULL,
    title VARCHAR(160) NOT NULL,
    description TEXT NOT NULL,
    sequence_no INTEGER NOT NULL,
    panorama_path VARCHAR(255) NOT NULL,
    initial_yaw REAL NOT NULL DEFAULT 0,
    initial_pitch REAL NOT NULL DEFAULT 0,
    initial_fov REAL NOT NULL DEFAULT 1.35,
    is_tutorial INTEGER NOT NULL DEFAULT 0,
    time_limit_seconds INTEGER NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('draft', 'active', 'inactive')),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (module_id, code),
    UNIQUE (module_id, sequence_no),
    FOREIGN KEY (module_id) REFERENCES training_modules(id)
);

CREATE TABLE hazards (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    scene_id INTEGER NOT NULL,
    code VARCHAR(30) NOT NULL,
    title VARCHAR(180) NOT NULL,
    category VARCHAR(100) NOT NULL,
    severity VARCHAR(20) NOT NULL CHECK (severity IN ('low', 'medium', 'high', 'critical')),
    yaw REAL NOT NULL,
    pitch REAL NOT NULL,
    hint TEXT NULL,
    is_assessed INTEGER NOT NULL DEFAULT 1,
    status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('draft', 'active', 'inactive')),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (scene_id, code),
    FOREIGN KEY (scene_id) REFERENCES scenes(id)
);

CREATE TABLE questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    hazard_id INTEGER NOT NULL UNIQUE,
    question_text TEXT NOT NULL,
    explanation TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('draft', 'active', 'inactive')),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hazard_id) REFERENCES hazards(id)
);

CREATE TABLE question_options (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    question_id INTEGER NOT NULL,
    option_text TEXT NOT NULL,
    is_correct INTEGER NOT NULL DEFAULT 0,
    sequence_no INTEGER NOT NULL,
    UNIQUE (question_id, sequence_no),
    FOREIGN KEY (question_id) REFERENCES questions(id)
);

CREATE TABLE training_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    module_id INTEGER NOT NULL,
    content_version INTEGER NOT NULL,
    content_snapshot TEXT NOT NULL,
    state VARCHAR(30) NOT NULL DEFAULT 'in_progress' CHECK (state IN ('created', 'in_progress', 'completed', 'timed_out', 'abandoned')),
    current_scene_id INTEGER NULL,
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    elapsed_seconds INTEGER NOT NULL DEFAULT 0,
    score INTEGER NOT NULL DEFAULT 0 CHECK (score >= 0),
    hazard_percent REAL NOT NULL DEFAULT 0,
    accuracy_percent REAL NOT NULL DEFAULT 0,
    passed INTEGER NULL,
    leaderboard_eligible INTEGER NOT NULL DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (module_id) REFERENCES training_modules(id),
    FOREIGN KEY (current_scene_id) REFERENCES scenes(id)
);

CREATE TABLE attempt_scene_progress (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    attempt_id INTEGER NOT NULL,
    scene_id INTEGER NOT NULL,
    state VARCHAR(30) NOT NULL DEFAULT 'not_started' CHECK (state IN ('not_started', 'in_progress', 'completed', 'timed_out')),
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    elapsed_seconds INTEGER NOT NULL DEFAULT 0,
    hazards_found INTEGER NOT NULL DEFAULT 0,
    correct_answers INTEGER NOT NULL DEFAULT 0,
    score INTEGER NOT NULL DEFAULT 0,
    UNIQUE (attempt_id, scene_id),
    FOREIGN KEY (attempt_id) REFERENCES training_attempts(id),
    FOREIGN KEY (scene_id) REFERENCES scenes(id)
);

CREATE TABLE hazard_responses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    attempt_id INTEGER NOT NULL,
    scene_id INTEGER NOT NULL,
    hazard_id INTEGER NOT NULL,
    question_id INTEGER NOT NULL,
    option_id INTEGER NOT NULL,
    is_correct INTEGER NOT NULL,
    discovery_points INTEGER NOT NULL DEFAULT 0,
    answer_points INTEGER NOT NULL DEFAULT 0,
    total_points INTEGER NOT NULL DEFAULT 0,
    client_event_id VARCHAR(80) NOT NULL,
    answered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (attempt_id, hazard_id),
    UNIQUE (attempt_id, client_event_id),
    FOREIGN KEY (attempt_id) REFERENCES training_attempts(id),
    FOREIGN KEY (scene_id) REFERENCES scenes(id),
    FOREIGN KEY (hazard_id) REFERENCES hazards(id),
    FOREIGN KEY (question_id) REFERENCES questions(id),
    FOREIGN KEY (option_id) REFERENCES question_options(id)
);

CREATE TABLE achievements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    description TEXT NOT NULL,
    criteria_code VARCHAR(80) NOT NULL
);

CREATE TABLE user_achievements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    achievement_id INTEGER NOT NULL,
    attempt_id INTEGER NOT NULL,
    awarded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (user_id, achievement_id, attempt_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (achievement_id) REFERENCES achievements(id),
    FOREIGN KEY (attempt_id) REFERENCES training_attempts(id)
);

CREATE INDEX idx_attempts_user ON training_attempts(user_id, started_at);
CREATE INDEX idx_attempts_module_state ON training_attempts(module_id, state, completed_at);
CREATE INDEX idx_responses_attempt ON hazard_responses(attempt_id);
CREATE INDEX idx_hazards_scene_status ON hazards(scene_id, status);

