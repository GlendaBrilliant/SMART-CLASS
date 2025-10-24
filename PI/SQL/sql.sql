CREATE DATABASE IF NOT EXISTS agendamento_aulas;
USE agendamento_aulas;

CREATE TABLE instituicoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    estado VARCHAR(50),
    cidade VARCHAR(50),
    endereco VARCHAR(150),
    telefone VARCHAR(20),
    logo VARCHAR(255),
    senha VARCHAR(255) NOT NULL,
    codigo_instituicao CHAR(4) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE professores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    estado VARCHAR(50),
    cidade VARCHAR(50),
    endereco VARCHAR(150),
    telefone VARCHAR(20),
    foto_perfil VARCHAR(255),
    senha VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE alunos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    estado VARCHAR(50),
    cidade VARCHAR(50),
    endereco VARCHAR(150),
    telefone VARCHAR(20),
    foto_perfil VARCHAR(255),
    senha VARCHAR(255) NOT NULL,
    instituicao_id INT DEFAULT NULL,
    turma_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (instituicao_id) REFERENCES instituicoes(id),
    FOREIGN KEY (turma_id) REFERENCES turmas(id)
);

CREATE TABLE turmas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instituicao_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    turno VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (instituicao_id) REFERENCES instituicoes(id)
);

CREATE TABLE disciplinas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    turma_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (turma_id) REFERENCES turmas(id)
);

CREATE TABLE professores_disciplinas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    disciplina_id INT NOT NULL,
    instituicao_id INT NOT NULL,
    FOREIGN KEY (professor_id) REFERENCES professores(id),
    FOREIGN KEY (disciplina_id) REFERENCES disciplinas(id),
    FOREIGN KEY (instituicao_id) REFERENCES instituicoes(id)
);

CREATE TABLE aulas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    disciplina_id INT NOT NULL,
    professor_id INT NOT NULL,
    turma_id INT NOT NULL,
    data DATE NOT NULL,
    horario TIME NOT NULL,
    sala VARCHAR(50),
    descricao TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (disciplina_id) REFERENCES disciplinas(id),
    FOREIGN KEY (professor_id) REFERENCES professores(id),
    FOREIGN KEY (turma_id) REFERENCES turmas(id)
);

CREATE TABLE presencas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aula_id INT NOT NULL,
    aluno_id INT NOT NULL,
    status ENUM('presente','falta','justificada') DEFAULT 'falta',
    FOREIGN KEY (aula_id) REFERENCES aulas(id),
    FOREIGN KEY (aluno_id) REFERENCES alunos(id)
);

CREATE TABLE solicitacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('aluno','professor') NOT NULL,
    usuario_id INT NOT NULL,
    instituicao_id INT NOT NULL,
    status ENUM('pendente','aceito','recusado') DEFAULT 'pendente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (instituicao_id) REFERENCES instituicoes(id)
);

CREATE TABLE afiliacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instituicao_id INT NOT NULL,
    usuario_tipo ENUM('aluno','professor') NOT NULL,
    usuario_id INT NOT NULL,
    turma_id INT DEFAULT NULL,
    status ENUM('pendente','ativa','cancelada') DEFAULT 'pendente',
    data_inicio DATE DEFAULT NULL,
    data_fim DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (instituicao_id) REFERENCES instituicoes(id) ON DELETE CASCADE
);

CREATE INDEX idx_afiliacao_usuario ON afiliacoes (usuario_tipo, usuario_id);
CREATE INDEX idx_afiliacao_instituicao ON afiliacoes (instituicao_id);




















================================================================================
























INSERT INTO alunos (nome, email, telefone, data_nascimento) VALUES
('Ana Beatriz Santos', 'ana.santos@gmail.com', '11987654321', '2007-03-12'),
('Lucas Henrique Souza', 'lucas.souza@gmail.com', '11988776655', '2006-11-04'),
('Mariana Oliveira Lima', 'mariana.lima@gmail.com', '11977889966', '2007-01-20'),
('Gabriel Almeida', 'gabriel.almeida@gmail.com', '11999887766', '2006-09-17'),
('Fernanda Ribeiro', 'fernanda.ribeiro@gmail.com', '11966554433', '2007-05-25'),
('Rafael Costa', 'rafael.costa@gmail.com', '11988775544', '2006-02-14'),
('Juliana Martins', 'juliana.martins@gmail.com', '11966557788', '2007-07-02'),
('Pedro Lima', 'pedro.lima@gmail.com', '11977665544', '2006-08-28'),
('Larissa Gomes', 'larissa.gomes@gmail.com', '11955443322', '2007-10-05'),
('Thiago Ferreira', 'thiago.ferreira@gmail.com', '11999885544', '2006-12-12');



INSERT INTO professores (nome, email, telefone, especialidade) VALUES
('Carlos Alberto', 'carlos.alberto@gmail.com', '11999887766', 'Matemática'),
('Patrícia Mendes', 'patricia.mendes@gmail.com', '11988776655', 'Português'),
('Roberto Lima', 'roberto.lima@gmail.com', '11977665544', 'História'),
('Camila Nogueira', 'camila.nogueira@gmail.com', '11966554433', 'Geografia'),
('André Oliveira', 'andre.oliveira@gmail.com', '11955443322', 'Física'),
('Juliana Alves', 'juliana.alves@gmail.com', '11944332211', 'Biologia'),
('Fernando Souza', 'fernando.souza@gmail.com', '11933221100', 'Educação Física'),
('Isabela Rocha', 'isabela.rocha@gmail.com', '11922110099', 'Inglês'),
('Ricardo Mendes', 'ricardo.mendes@gmail.com', '11911009988', 'Química'),
('Tatiane Ribeiro', 'tatiane.ribeiro@gmail.com', '11900998877', 'Artes');