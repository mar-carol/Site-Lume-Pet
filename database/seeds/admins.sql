
-- Contas de demonstração. Troque as credenciais antes de usar em produção.
INSERT INTO usuarios (nome, email, senha_hash, telefone, endereco, tipo)
VALUES ('Administrador', 'admin@lumepet.com', '$2b$10$D2oL8jfafZMGb/0eF1i7uuxIJ579kKjiqLjym4N83rhzyHE7yonI2', '(00) 00000-0000', 'Sede Lume Pet', 'admin')
ON DUPLICATE KEY UPDATE id = id;

INSERT INTO usuarios (nome, email, senha_hash, telefone, endereco, tipo)
VALUES
('Admin Zambianco', 'admin1@lumepet.com', '$2b$10$57kf6x81Q0L9LIn5muimXuPmq9F19trsVrwCqDpzKgkSa2TicNnAq', '(00) 00000-0000', 'Sede Lume Pet', 'admin'),
('Admin Loris', 'admin2@lumepet.com', '$2b$10$.7qpi7DMRSGAM/Vrtx1JWu/gCjDsXhNFiX9yJ7ZI9iSbSwc5wHlP2', '(00) 00000-0000', 'Sede Lume Pet', 'admin'),
('Admin Miciano', 'admin3@lumepet.com', '$2b$10$VzJCKg62hXzMq3vnoZOStuqepEXz/oF0axxnANGgxda9450QS7YAe', '(00) 00000-0000', 'Sede Lume Pet', 'admin'),
('Admin Duda', 'admin4@lumepet.com', '$2b$10$TQWDxhLcIsuR9lNtA46Zfuu8zbhuX4Wp44cLwpUkGiUM15bpEK/t6', '(00) 00000-0000', 'Sede Lume Pet', 'admin'),
('Admin Couto', 'admin5@lumepet.com', '$2b$10$pjQXJ9WsA14eEfBqS6ykLu9DwhRRlFAj5GDR43Dz.vruT//d6aBfu', '(00) 00000-0000', 'Sede Lume Pet', 'admin')
ON DUPLICATE KEY UPDATE id = id;
