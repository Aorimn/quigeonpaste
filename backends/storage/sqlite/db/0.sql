PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS namespaces (
	name TEXT UNIQUE NOT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS idx_ns ON namespaces(name);

CREATE TABLE IF NOT EXISTS types (
	name TEXT UNIQUE NOT NULL
);

CREATE TABLE IF NOT EXISTS pastes (
	id TEXT PRIMARY KEY,
	ns INT,
	title TEXT,
	content TEXT NOT NULL,
	type INT DEFAULT 0,      -- Default type 0 means 'no type'
	delay INT,
	acl TEXT,
	once TINYINT,
	owner TEXT,
	FOREIGN KEY(ns) REFERENCES namespaces(rowid)
);

CREATE TABLE IF NOT EXISTS version (
	current INT KEY NOT NULL
);

/* Default insertions */
/* Note: insertion of types is done in the PHP code */
INSERT INTO namespaces VALUES ("");

INSERT INTO version VALUES (0);
