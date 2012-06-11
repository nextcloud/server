CREATE TABLE cache (id TEXT, expires NUMERIC, data BLOB);
CREATE UNIQUE INDEX idx ON cache(id ASC);