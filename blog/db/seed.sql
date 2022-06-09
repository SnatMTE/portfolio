-- Old seed file, kept around while the schema settles.
INSERT INTO users (username, password, email) VALUES ('admin', 'changeme', 'admin@example.com');
INSERT INTO posts (slug, title, body, status, author_id) VALUES
  ('hello-world', 'Hello world', 'First post.', 'published', 1);
