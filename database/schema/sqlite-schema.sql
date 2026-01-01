CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "email_verified_at" datetime,
  "password" varchar not null,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "is_admin" tinyint(1) not null default '0'
);
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS "exercises"(
  "id" integer primary key autoincrement not null,
  "user_id" integer,
  "name" varchar not null,
  "description" text,
  "instructions" text,
  "difficulty_level" varchar,
  "category" varchar,
  "default_duration_seconds" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "exercises_user_id_index" on "exercises"("user_id");
CREATE INDEX "exercises_category_index" on "exercises"("category");
CREATE TABLE IF NOT EXISTS "exercise_progressions"(
  "id" integer primary key autoincrement not null,
  "exercise_id" integer not null,
  "easier_exercise_id" integer,
  "harder_exercise_id" integer,
  "order" integer not null default '0',
  "progression_path_name" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("exercise_id") references "exercises"("id") on delete cascade,
  foreign key("easier_exercise_id") references "exercises"("id") on delete set null,
  foreign key("harder_exercise_id") references "exercises"("id") on delete set null
);
CREATE INDEX "exercise_progressions_exercise_id_index" on "exercise_progressions"(
  "exercise_id"
);
CREATE INDEX "exercise_progressions_progression_path_name_index" on "exercise_progressions"(
  "progression_path_name"
);
CREATE TABLE IF NOT EXISTS "session_templates"(
  "id" integer primary key autoincrement not null,
  "user_id" integer,
  "name" varchar not null,
  "description" text,
  "notes" text,
  "default_rest_seconds" integer not null default '60',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "session_templates_user_id_index" on "session_templates"(
  "user_id"
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "session_template_id" integer,
  "name" varchar,
  "notes" text,
  "started_at" datetime,
  "completed_at" datetime,
  "total_duration_seconds" integer,
  "status" varchar not null default 'planned',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("session_template_id") references "session_templates"("id") on delete set null
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_session_template_id_index" on "sessions"(
  "session_template_id"
);
CREATE INDEX "sessions_status_index" on "sessions"("status");
CREATE INDEX "sessions_user_id_created_at_index" on "sessions"(
  "user_id",
  "created_at"
);
CREATE INDEX "sessions_user_id_completed_at_index" on "sessions"(
  "user_id",
  "completed_at"
);
CREATE TABLE IF NOT EXISTS "session_exercises"(
  "id" integer primary key autoincrement not null,
  "session_id" integer not null,
  "exercise_id" integer not null,
  "order" integer not null default '0',
  "duration_seconds" integer,
  "notes" text,
  "difficulty_rating" varchar,
  "started_at" datetime,
  "completed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("session_id") references "sessions"("id") on delete cascade,
  foreign key("exercise_id") references "exercises"("id") on delete cascade
);
CREATE INDEX "session_exercises_session_id_index" on "session_exercises"(
  "session_id"
);
CREATE INDEX "session_exercises_exercise_id_index" on "session_exercises"(
  "exercise_id"
);
CREATE INDEX "session_exercises_session_id_order_index" on "session_exercises"(
  "session_id",
  "order"
);
CREATE TABLE IF NOT EXISTS "user_goals"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "sessions_per_week" integer not null default '3',
  "minimum_session_duration_minutes" integer not null default '10',
  "is_active" tinyint(1) not null default '1',
  "starts_at" date not null,
  "ends_at" date,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "user_goals_user_id_index" on "user_goals"("user_id");
CREATE INDEX "user_goals_user_id_is_active_index" on "user_goals"(
  "user_id",
  "is_active"
);
CREATE INDEX "user_goals_user_id_starts_at_ends_at_index" on "user_goals"(
  "user_id",
  "starts_at",
  "ends_at"
);
CREATE TABLE IF NOT EXISTS "user_exercise_progress"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "exercise_id" integer not null,
  "status" varchar not null default 'current',
  "best_sets" integer,
  "best_reps" integer,
  "best_duration_seconds" integer,
  "mastered_at" datetime,
  "started_at" datetime,
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("exercise_id") references "exercises"("id") on delete cascade
);
CREATE UNIQUE INDEX "user_exercise_progress_user_id_exercise_id_unique" on "user_exercise_progress"(
  "user_id",
  "exercise_id"
);
CREATE INDEX "user_exercise_progress_user_id_index" on "user_exercise_progress"(
  "user_id"
);
CREATE INDEX "user_exercise_progress_user_id_status_index" on "user_exercise_progress"(
  "user_id",
  "status"
);
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "notifications"(
  "id" varchar not null,
  "type" varchar not null,
  "notifiable_type" varchar not null,
  "notifiable_id" integer not null,
  "data" text not null,
  "read_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  primary key("id")
);
CREATE INDEX "notifications_notifiable_type_notifiable_id_index" on "notifications"(
  "notifiable_type",
  "notifiable_id"
);
CREATE TABLE IF NOT EXISTS "imports"(
  "id" integer primary key autoincrement not null,
  "completed_at" datetime,
  "file_name" varchar not null,
  "file_path" varchar not null,
  "importer" varchar not null,
  "processed_rows" integer not null default '0',
  "total_rows" integer not null,
  "successful_rows" integer not null default '0',
  "user_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "exports"(
  "id" integer primary key autoincrement not null,
  "completed_at" datetime,
  "file_disk" varchar not null,
  "file_name" varchar,
  "exporter" varchar not null,
  "processed_rows" integer not null default '0',
  "total_rows" integer not null,
  "successful_rows" integer not null default '0',
  "user_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "failed_import_rows"(
  "id" integer primary key autoincrement not null,
  "data" text not null,
  "import_id" integer not null,
  "validation_error" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("import_id") references "imports"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "session_template_exercises"(
  "id" integer primary key autoincrement not null,
  "session_template_id" integer not null,
  "exercise_id" integer not null,
  "order" integer not null default('0'),
  "duration_seconds" integer,
  "rest_after_seconds" integer,
  "sets" integer,
  "reps" integer,
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("exercise_id") references exercises("id") on delete cascade on update no action,
  foreign key("session_template_id") references session_templates("id") on delete cascade on update no action
);
CREATE INDEX "session_template_exercises_exercise_id_index" on "session_template_exercises"(
  "exercise_id"
);
CREATE INDEX "session_template_exercises_session_template_id_index" on "session_template_exercises"(
  "session_template_id"
);
CREATE UNIQUE INDEX "session_template_exercises_session_template_id_order_unique" on "session_template_exercises"(
  "session_template_id",
  "order"
);

INSERT INTO migrations VALUES(1,'2014_10_12_000000_create_users_table',1);
INSERT INTO migrations VALUES(2,'2019_08_19_000000_create_failed_jobs_table',1);
INSERT INTO migrations VALUES(11,'2025_12_31_221319_create_exercises_table',2);
INSERT INTO migrations VALUES(12,'2025_12_31_221320_create_exercise_progressions_table',2);
INSERT INTO migrations VALUES(13,'2025_12_31_221321_create_session_templates_table',2);
INSERT INTO migrations VALUES(14,'2025_12_31_221322_create_session_template_exercises_table',2);
INSERT INTO migrations VALUES(15,'2025_12_31_221323_create_sessions_table',2);
INSERT INTO migrations VALUES(16,'2025_12_31_221324_create_session_exercises_table',2);
INSERT INTO migrations VALUES(17,'2025_12_31_221325_create_user_goals_table',2);
INSERT INTO migrations VALUES(18,'2025_12_31_221326_create_user_exercise_progress_table',2);
INSERT INTO migrations VALUES(19,'2025_12_31_233916_remove_unique_constraint_from_exercise_progressions',3);
INSERT INTO migrations VALUES(20,'2025_12_31_234247_create_job_batches_table',4);
INSERT INTO migrations VALUES(21,'2025_12_31_234247_create_notifications_table',4);
INSERT INTO migrations VALUES(22,'2025_12_31_234248_create_imports_table',4);
INSERT INTO migrations VALUES(23,'2025_12_31_234249_create_exports_table',4);
INSERT INTO migrations VALUES(24,'2025_12_31_234250_create_failed_import_rows_table',4);
INSERT INTO migrations VALUES(25,'2025_12_31_235307_make_sets_reps_nullable_in_session_template_exercises',5);
INSERT INTO migrations VALUES(26,'2026_01_01_000509_add_is_admin_to_users_table',6);
INSERT INTO migrations VALUES(27,'2026_01_01_004927_remove_estimated_duration_minutes_from_session_templates',7);
