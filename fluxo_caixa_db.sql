
CREATE DATABASE IF NOT EXISTS `fluxocaixa` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `fluxocaixa`;

CREATE TABLE `despesas` (
  `id` int(11) NOT NULL,
  `data` date NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `descricao` text NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `entradas` (
  `id` int(11) NOT NULL,
  `data` date NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `descricao` text,
  `tipo` enum('dinheiro','outro') DEFAULT 'dinheiro',
  `usuario_id` int(11) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sangrias` (
  `id` int(11) NOT NULL,
  `data` date NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `caixa` varchar(50) DEFAULT NULL,
  `destino` enum('caixa','banco') DEFAULT 'caixa',
  `usuario_id` int(11) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Primary keys
ALTER TABLE `despesas`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `entradas`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `sangrias`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

-- Performance indexes
ALTER TABLE `despesas`
  ADD KEY `idx_data` (`data`),
  ADD KEY `idx_usuario_data` (`usuario_id`, `data`),
  ADD KEY `idx_data_valor` (`data`, `valor`);

ALTER TABLE `entradas`
  ADD KEY `idx_data` (`data`),
  ADD KEY `idx_usuario_data` (`usuario_id`, `data`),
  ADD KEY `idx_data_valor` (`data`, `valor`);

ALTER TABLE `sangrias`
  ADD KEY `idx_data` (`data`),
  ADD KEY `idx_usuario_data` (`usuario_id`, `data`),
  ADD KEY `idx_data_destino` (`data`, `destino`),
  ADD KEY `idx_data_valor` (`data`, `valor`);

-- Auto increment
ALTER TABLE `despesas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `entradas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `sangrias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Foreign keys
ALTER TABLE `despesas`
  ADD CONSTRAINT `despesas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

ALTER TABLE `entradas`
  ADD CONSTRAINT `entradas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

ALTER TABLE `sangrias`
  ADD CONSTRAINT `sangrias_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

COMMIT;


