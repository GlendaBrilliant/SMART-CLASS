-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 24/10/2025 às 17:44
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `agendamento_aulas`
--
CREATE DATABASE IF NOT EXISTS `agendamento_aulas` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `agendamento_aulas`;

-- --------------------------------------------------------

--
-- Estrutura para tabela `afiliacoes`
--

CREATE TABLE `afiliacoes` (
  `id` int(11) NOT NULL,
  `instituicao_id` int(11) NOT NULL,
  `usuario_tipo` enum('aluno','professor') NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `turma_id` int(11) DEFAULT NULL,
  `status` enum('pendente','ativa','cancelada') DEFAULT 'pendente',
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `afiliacoes`
--

INSERT INTO `afiliacoes` (`id`, `instituicao_id`, `usuario_tipo`, `usuario_id`, `turma_id`, `status`, `data_inicio`, `data_fim`, `created_at`, `updated_at`) VALUES
(1, 5, 'professor', 14, NULL, 'ativa', '2025-10-12', NULL, '2025-10-12 16:58:36', NULL),
(2, 5, 'aluno', 12, 1, 'ativa', '2025-10-12', NULL, '2025-10-12 17:50:35', NULL),
(4, 5, 'aluno', 14, 1, 'ativa', '2025-10-12', NULL, '2025-10-12 19:07:13', NULL),
(5, 5, 'aluno', 15, 1, 'ativa', '2025-10-12', NULL, '2025-10-12 19:51:19', NULL),
(6, 5, 'professor', 15, NULL, 'ativa', '2025-10-12', NULL, '2025-10-12 20:06:26', NULL),
(7, 5, 'professor', 16, NULL, 'ativa', '2025-10-12', NULL, '2025-10-12 20:06:37', NULL),
(8, 5, 'professor', 17, NULL, 'ativa', '2025-10-12', NULL, '2025-10-12 22:06:55', NULL),
(9, 5, 'professor', 18, NULL, 'ativa', '2025-10-12', NULL, '2025-10-12 22:13:46', NULL),
(10, 7, 'professor', 15, NULL, 'ativa', '2025-10-19', NULL, '2025-10-20 02:23:42', NULL),
(11, 5, 'professor', 19, NULL, 'ativa', '2025-10-20', NULL, '2025-10-20 17:04:59', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `alunos`
--

CREATE TABLE `alunos` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `estado` varchar(50) DEFAULT NULL,
  `cidade` varchar(50) DEFAULT NULL,
  `endereco` varchar(150) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `senha` varchar(255) NOT NULL,
  `instituicao_id` int(11) DEFAULT NULL,
  `turma_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `alunos`
--

INSERT INTO `alunos` (`id`, `nome`, `email`, `estado`, `cidade`, `endereco`, `telefone`, `foto_perfil`, `senha`, `instituicao_id`, `turma_id`, `created_at`) VALUES
(14, 'José', 'jose@gmail.com', 'São Paulo', 'Taubaté', 'avenida jose augusto', '1212345678', '1760295982_iStock-1275679638.jpg', '$2y$10$LY.AsMINYZSkxEqzH1Un0ObM7D8yyHDnl05E7./w4jhcwCTLQijqC', 5, 2, '2025-10-12 19:06:22'),
(15, 'Flávio', 'fl.augusto0102@gmail.com', 'São Paulo', 'Taubaté', 'avenida jose augusto', '1212345678', '1760298578_cloudy-sky-and-luffy-smile-8vbikpwmy526gxcz.jpg', '$2y$10$S.sB9NGlpfqBvRZPr8Jw1.wKyYxVZVe4VHgxgdT7GU73/z.K9gCc6', 5, 1, '2025-10-12 19:49:38'),
(16, 'André', 'andre@gmail.com', 'São Paulo', 'Taubaté', 'avenida jose augusto', '1212345678', '1761305334_Capa-Guia-do-estudante-brasileiro.jpg', '$2y$10$Rt63fSC1NdmzikXUX4Q8VuJxwNiZum19ULSH8UTwLjtGML.yvMfLi', NULL, NULL, '2025-10-24 11:28:54'),
(17, 'Mariana', 'mariana@gmail.com', 'São Paulo', 'Taubaté', 'avenida jose augusto', '1212345678', '1761305381_photo-1618355776464-8666794d2520.jpg', '$2y$10$iOXxlwaapwWi196LmEVSBu/NXdfSRHa4rCgF8vDpD4rs6WZ0s8vGK', NULL, NULL, '2025-10-24 11:29:41');

-- --------------------------------------------------------

--
-- Estrutura para tabela `aulas`
--

CREATE TABLE `aulas` (
  `id` int(11) NOT NULL,
  `disciplina_id` int(11) NOT NULL,
  `professor_id` int(11) NOT NULL,
  `turma_id` int(11) NOT NULL,
  `data` date NOT NULL,
  `horario` time NOT NULL,
  `sala` varchar(50) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `aulas`
--

INSERT INTO `aulas` (`id`, `disciplina_id`, `professor_id`, `turma_id`, `data`, `horario`, `sala`, `descricao`, `created_at`) VALUES
(1, 6, 15, 2, '2025-10-16', '23:40:00', '', '', '2025-10-12 23:39:03'),
(3, 2, 15, 1, '2025-10-23', '08:30:00', 'Sala 05', '', '2025-10-19 03:35:35'),
(4, 6, 15, 2, '2025-10-19', '10:30:00', 'Sala 05', 'aula legall, aula legall, aula legall, aula legall aula legall, aula legall, aula legall aula legall aula legall aula legall', '2025-10-19 21:35:50'),
(5, 2, 15, 1, '2025-10-19', '08:50:00', '', '', '2025-10-20 01:52:32'),
(6, 2, 15, 1, '2025-10-15', '10:00:00', 'Sala 10', '', '2025-10-20 01:52:51'),
(7, 2, 15, 1, '2025-10-10', '16:06:00', '', '', '2025-10-20 17:06:17');

-- --------------------------------------------------------

--
-- Estrutura para tabela `disciplinas`
--

CREATE TABLE `disciplinas` (
  `id` int(11) NOT NULL,
  `turma_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `cor` varchar(7) DEFAULT '#f0f0f0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `disciplinas`
--

INSERT INTO `disciplinas` (`id`, `turma_id`, `nome`, `created_at`, `cor`) VALUES
(2, 1, 'Matemática', '2025-10-12 17:06:06', '#709bff'),
(3, 2, 'Matemática', '2025-10-12 17:28:24', '#758cff'),
(4, 1, 'Inglês', '2025-10-12 20:05:29', '#ff6b6b'),
(5, 1, 'Programação Web', '2025-10-12 20:05:44', '#296e7f'),
(6, 2, 'Inglês', '2025-10-12 20:17:56', '#ff6666'),
(7, 3, 'Matemática', '2025-10-20 02:23:34', '#8c8aff'),
(8, 2, 'Programação Web', '2025-10-20 17:04:30', '#94ffaf'),
(9, 7, 'Matemática', '2025-10-24 11:34:07', '#7ab4ff'),
(10, 7, 'Inglês', '2025-10-24 11:34:21', '#ff666e'),
(11, 7, 'Português', '2025-10-24 11:34:32', '#ffac75'),
(12, 7, 'Logística', '2025-10-24 11:34:55', '#94ffea'),
(13, 6, 'Matemática', '2025-10-24 11:35:17', '#80c8ff'),
(14, 6, 'Inglês', '2025-10-24 11:35:25', '#ff6b6b'),
(15, 6, 'Português', '2025-10-24 11:35:40', '#ff9861'),
(16, 6, 'Administração', '2025-10-24 11:36:05', '#0ba300'),
(17, 5, 'Matemática', '2025-10-24 11:36:36', '#8593ff'),
(18, 5, 'Inglês', '2025-10-24 11:36:57', '#ff6b6b'),
(19, 5, 'Português', '2025-10-24 11:37:14', '#ff976b'),
(20, 5, 'Administração', '2025-10-24 11:37:28', '#0cb300'),
(21, 4, 'Matemática', '2025-10-24 11:37:36', '#75bcff'),
(22, 4, 'Inglês', '2025-10-24 11:37:42', '#ff7070'),
(23, 4, 'Português', '2025-10-24 11:37:51', '#ff955c'),
(24, 4, 'Desenvolvimento Web', '2025-10-24 11:38:18', '#70fff5');

-- --------------------------------------------------------

--
-- Estrutura para tabela `instituicoes`
--

CREATE TABLE `instituicoes` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `estado` varchar(50) DEFAULT NULL,
  `cidade` varchar(50) DEFAULT NULL,
  `endereco` varchar(150) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `senha` varchar(255) NOT NULL,
  `codigo_instituicao` char(4) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `instituicoes`
--

INSERT INTO `instituicoes` (`id`, `nome`, `email`, `estado`, `cidade`, `endereco`, `telefone`, `logo`, `senha`, `codigo_instituicao`, `created_at`) VALUES
(5, 'Fatec', 'contato@fatec.com', 'São Paulo', 'Taubaté', 'avenida jose augusto', '1212345678', '', '$2y$10$T.itXP7XMbHNG9h3PetrMuYvi2HWShb/NSe0imfmoTXqVvHC.AvFW', '1234', '2025-10-12 16:40:25'),
(7, 'Alfa', 'contato@alfa.com', 'São Paulo', 'Taubaté', 'avenida jose augusto', '1212345678', '', '$2y$10$HfhMpkWTUV4/PSWLbhWtlenuPSp.isTPQV8EQgzpeLwvkXKPvSSNG', '4321', '2025-10-12 16:43:08');

-- --------------------------------------------------------

--
-- Estrutura para tabela `presencas`
--

CREATE TABLE `presencas` (
  `id` int(11) NOT NULL,
  `aula_id` int(11) NOT NULL,
  `aluno_id` int(11) NOT NULL,
  `status` enum('presente','falta','justificada') DEFAULT 'falta'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `presencas`
--

INSERT INTO `presencas` (`id`, `aula_id`, `aluno_id`, `status`) VALUES
(1, 4, 14, 'presente'),
(2, 3, 15, 'falta'),
(3, 1, 14, 'presente');

-- --------------------------------------------------------

--
-- Estrutura para tabela `professores`
--

CREATE TABLE `professores` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `estado` varchar(50) DEFAULT NULL,
  `cidade` varchar(50) DEFAULT NULL,
  `endereco` varchar(150) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `senha` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `professores`
--

INSERT INTO `professores` (`id`, `nome`, `email`, `estado`, `cidade`, `endereco`, `telefone`, `foto_perfil`, `senha`, `created_at`) VALUES
(15, 'Carlos', 'carlos@gmail.com', 'São Paulo', 'Taubaté', 'avenida jose augusto', '1212345678', 'uploads/prof_15_1760924011.png', '$2y$10$5S0aPB8v5JwcX2G3.FF1g.E0lluKk3kQBGcNCqKuqnfHeMIv.EZgW', '2025-10-12 20:01:54'),
(16, 'Lívia', 'livia@gmail.com', 'São Paulo', 'Taubaté', 'avenida jose augusto', '1212345678', 'uploads/1760299375_01b9c85c8a0192a372e527d80b4129bb.jpg', '$2y$10$ieEu5menVkOyClfPPUbv0Op0NFAAH3F06/6t5Gh.xMkBl/v6En3dO', '2025-10-12 20:02:55'),
(17, 'José', 'jose@gmail.com', 'São Paulo', 'Taubaté', 'avenida jose augusto', '1212345678', '', '$2y$10$5GbQQ5/ra5EkO3Roavzp1OYPAiXpEL8TrEE//F/btd5qML/puhefa', '2025-10-12 22:06:21'),
(18, 'Ana', 'ana@gmail.com', 'São Paulo', 'Taubaté', 'avenida jose augusto', '1212345678', '1760306883_whatsapp-image-2022-10-03-at-15.34.37.webp', '$2y$10$j9sb9x9QV2bsD4GI/vq1bue0KNgpkf20jN/hZZzynmmnpHMWAlefy', '2025-10-12 22:08:03'),
(19, 'João', 'Joj@gmail.com', 'São Paulo', 'Taubaté', 'avenida jose augusto', '1212345678', '', '$2y$10$S.ZhPFLsOxhhlBamAbXSE.qKN2y.19qGv0vrZThUWgZMpcv5F71Ji', '2025-10-20 17:02:53'),
(20, 'Gustavo', 'gustavo@gmail.com', 'São Paulo', 'Taubaté', 'avenida jose augusto', '1212345678', '', '$2y$10$cMguABLPFeUbLPPlLssbt.xvJCDgtJKIzuNMCcQ0GWvynCM101aVe', '2025-10-24 11:24:30'),
(21, 'Antônio', 'antonio@gmail.com', 'São Paulo', 'Taubaté', 'avenida jose augusto', '1212345678', '1761305211_retrato_corporativo_de_busto_e_palet_e_gravata_para_perfil_profissional_-_estdio_fotogrfico_em_sp.jpg', '$2y$10$5srAruC0S5y69mxS3xLeHOus86erLVJKafvhXh7rLuLOygL6Ti0Le', '2025-10-24 11:26:51');

-- --------------------------------------------------------

--
-- Estrutura para tabela `professores_disciplinas`
--

CREATE TABLE `professores_disciplinas` (
  `id` int(11) NOT NULL,
  `professor_id` int(11) NOT NULL,
  `disciplina_id` int(11) NOT NULL,
  `instituicao_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `professores_disciplinas`
--

INSERT INTO `professores_disciplinas` (`id`, `professor_id`, `disciplina_id`, `instituicao_id`) VALUES
(5, 16, 4, 5),
(6, 16, 5, 5),
(7, 15, 6, 5),
(8, 17, 3, 5),
(10, 15, 2, 5),
(11, 15, 7, 7),
(12, 19, 8, 5);

-- --------------------------------------------------------

--
-- Estrutura para tabela `solicitacoes`
--

CREATE TABLE `solicitacoes` (
  `id` int(11) NOT NULL,
  `tipo` enum('aluno','professor') NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `instituicao_id` int(11) NOT NULL,
  `status` enum('pendente','aceito','recusado') DEFAULT 'pendente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `solicitacoes`
--

INSERT INTO `solicitacoes` (`id`, `tipo`, `usuario_id`, `instituicao_id`, `status`, `created_at`) VALUES
(29, 'professor', 14, 5, 'aceito', '2025-10-12 16:46:33'),
(30, 'aluno', 12, 5, 'aceito', '2025-10-12 17:48:45'),
(31, 'aluno', 13, 5, 'aceito', '2025-10-12 18:42:27'),
(32, 'aluno', 14, 5, 'aceito', '2025-10-12 19:06:35'),
(33, 'aluno', 15, 5, 'aceito', '2025-10-12 19:49:57'),
(34, 'professor', 15, 5, 'aceito', '2025-10-12 20:02:06'),
(35, 'professor', 16, 5, 'aceito', '2025-10-12 20:03:06'),
(36, 'professor', 17, 5, 'aceito', '2025-10-12 22:06:29'),
(37, 'professor', 18, 5, 'aceito', '2025-10-12 22:08:12'),
(38, 'professor', 15, 7, 'aceito', '2025-10-20 02:22:42'),
(39, 'professor', 19, 5, 'aceito', '2025-10-20 17:03:09'),
(40, 'professor', 20, 5, 'pendente', '2025-10-24 11:25:30'),
(41, 'professor', 21, 5, 'pendente', '2025-10-24 11:27:02'),
(42, 'aluno', 16, 5, 'pendente', '2025-10-24 11:29:04'),
(43, 'aluno', 17, 5, 'pendente', '2025-10-24 11:29:50');

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `solicitacoes_alunos`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `solicitacoes_alunos` (
`id` int(11)
,`aluno_id` int(11)
,`nome` varchar(100)
,`email` varchar(100)
,`status` enum('pendente','aceito','recusado')
,`instituicao_id` int(11)
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `solicitacoes_professores`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `solicitacoes_professores` (
`id` int(11)
,`professor_id` int(11)
,`nome` varchar(100)
,`email` varchar(100)
,`status` enum('pendente','aceito','recusado')
,`instituicao_id` int(11)
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Estrutura para tabela `turmas`
--

CREATE TABLE `turmas` (
  `id` int(11) NOT NULL,
  `instituicao_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `turno` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `turmas`
--

INSERT INTO `turmas` (`id`, `instituicao_id`, `nome`, `turno`, `created_at`) VALUES
(1, 5, '1 AMS', 'Manhã', '2025-10-12 16:57:00'),
(2, 5, '2 LOG', 'Noite', '2025-10-12 17:20:26'),
(3, 7, '1 ADS', 'Manhã', '2025-10-20 02:23:08'),
(4, 5, '1 ADS', 'Tarde', '2025-10-24 11:33:07'),
(5, 5, '2 ADM', 'Manhã', '2025-10-24 11:33:24'),
(6, 5, '1 ADM', 'Manhã', '2025-10-24 11:33:33'),
(7, 5, '1 LOG', 'Manhã', '2025-10-24 11:33:39');

-- --------------------------------------------------------

--
-- Estrutura para view `solicitacoes_alunos`
--
DROP TABLE IF EXISTS `solicitacoes_alunos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `solicitacoes_alunos`  AS SELECT `s`.`id` AS `id`, `s`.`usuario_id` AS `aluno_id`, `a`.`nome` AS `nome`, `a`.`email` AS `email`, `s`.`status` AS `status`, `s`.`instituicao_id` AS `instituicao_id`, `s`.`created_at` AS `created_at` FROM (`solicitacoes` `s` join `alunos` `a` on(`s`.`usuario_id` = `a`.`id`)) WHERE `s`.`tipo` = 'aluno' ;

-- --------------------------------------------------------

--
-- Estrutura para view `solicitacoes_professores`
--
DROP TABLE IF EXISTS `solicitacoes_professores`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `solicitacoes_professores`  AS SELECT `s`.`id` AS `id`, `s`.`usuario_id` AS `professor_id`, `p`.`nome` AS `nome`, `p`.`email` AS `email`, `s`.`status` AS `status`, `s`.`instituicao_id` AS `instituicao_id`, `s`.`created_at` AS `created_at` FROM (`solicitacoes` `s` join `professores` `p` on(`s`.`usuario_id` = `p`.`id`)) WHERE `s`.`tipo` = 'professor' ;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `afiliacoes`
--
ALTER TABLE `afiliacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_afiliacao_usuario` (`usuario_tipo`,`usuario_id`),
  ADD KEY `idx_afiliacao_instituicao` (`instituicao_id`);

--
-- Índices de tabela `alunos`
--
ALTER TABLE `alunos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `instituicao_id` (`instituicao_id`),
  ADD KEY `turma_id` (`turma_id`);

--
-- Índices de tabela `aulas`
--
ALTER TABLE `aulas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `disciplina_id` (`disciplina_id`),
  ADD KEY `professor_id` (`professor_id`),
  ADD KEY `turma_id` (`turma_id`);

--
-- Índices de tabela `disciplinas`
--
ALTER TABLE `disciplinas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `turma_id` (`turma_id`);

--
-- Índices de tabela `instituicoes`
--
ALTER TABLE `instituicoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `codigo_instituicao` (`codigo_instituicao`);

--
-- Índices de tabela `presencas`
--
ALTER TABLE `presencas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aula_id` (`aula_id`),
  ADD KEY `aluno_id` (`aluno_id`);

--
-- Índices de tabela `professores`
--
ALTER TABLE `professores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `professores_disciplinas`
--
ALTER TABLE `professores_disciplinas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `professor_id` (`professor_id`),
  ADD KEY `disciplina_id` (`disciplina_id`),
  ADD KEY `instituicao_id` (`instituicao_id`);

--
-- Índices de tabela `solicitacoes`
--
ALTER TABLE `solicitacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `instituicao_id` (`instituicao_id`);

--
-- Índices de tabela `turmas`
--
ALTER TABLE `turmas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `instituicao_id` (`instituicao_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `afiliacoes`
--
ALTER TABLE `afiliacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `alunos`
--
ALTER TABLE `alunos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de tabela `aulas`
--
ALTER TABLE `aulas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `disciplinas`
--
ALTER TABLE `disciplinas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de tabela `instituicoes`
--
ALTER TABLE `instituicoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `presencas`
--
ALTER TABLE `presencas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `professores`
--
ALTER TABLE `professores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de tabela `professores_disciplinas`
--
ALTER TABLE `professores_disciplinas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `solicitacoes`
--
ALTER TABLE `solicitacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT de tabela `turmas`
--
ALTER TABLE `turmas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `afiliacoes`
--
ALTER TABLE `afiliacoes`
  ADD CONSTRAINT `afiliacoes_ibfk_1` FOREIGN KEY (`instituicao_id`) REFERENCES `instituicoes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `alunos`
--
ALTER TABLE `alunos`
  ADD CONSTRAINT `alunos_ibfk_1` FOREIGN KEY (`instituicao_id`) REFERENCES `instituicoes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `alunos_ibfk_2` FOREIGN KEY (`turma_id`) REFERENCES `turmas` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `aulas`
--
ALTER TABLE `aulas`
  ADD CONSTRAINT `aulas_ibfk_1` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `aulas_ibfk_2` FOREIGN KEY (`professor_id`) REFERENCES `professores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `aulas_ibfk_3` FOREIGN KEY (`turma_id`) REFERENCES `turmas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `disciplinas`
--
ALTER TABLE `disciplinas`
  ADD CONSTRAINT `disciplinas_ibfk_1` FOREIGN KEY (`turma_id`) REFERENCES `turmas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `presencas`
--
ALTER TABLE `presencas`
  ADD CONSTRAINT `presencas_ibfk_1` FOREIGN KEY (`aula_id`) REFERENCES `aulas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `presencas_ibfk_2` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `professores_disciplinas`
--
ALTER TABLE `professores_disciplinas`
  ADD CONSTRAINT `professores_disciplinas_ibfk_1` FOREIGN KEY (`professor_id`) REFERENCES `professores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `professores_disciplinas_ibfk_2` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `professores_disciplinas_ibfk_3` FOREIGN KEY (`instituicao_id`) REFERENCES `instituicoes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `solicitacoes`
--
ALTER TABLE `solicitacoes`
  ADD CONSTRAINT `solicitacoes_ibfk_1` FOREIGN KEY (`instituicao_id`) REFERENCES `instituicoes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `turmas`
--
ALTER TABLE `turmas`
  ADD CONSTRAINT `turmas_ibfk_1` FOREIGN KEY (`instituicao_id`) REFERENCES `instituicoes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
