SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `file` (
  `id` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `folderId` int(11) NOT NULL,
  `serverName` varchar(60) NOT NULL,
  `origenName` varchar(255) NOT NULL,
  `mimeType` varchar(127) NOT NULL,
  `size` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `folder` (
  `id` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `parentId` int(11) DEFAULT 0,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `folder` (`id`, `userId`, `parentId`, `name`) VALUES
(0, 0, 0, 'GLOBAL_ROOT');

CREATE TABLE `reset_password` (
  `id` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `hashedToken` varchar(255) NOT NULL,
  `expiresAt` datetime NOT NULL,
  `createdAt` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `share` (
  `id` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `fileId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `passwordEncrypted` varchar(255) NOT NULL,
  `role` varchar(150) NOT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `user` (`id`, `email`, `passwordEncrypted`, `role`, `age`, `gender`) VALUES
(0, 'system', '$2y$10$hJ7RJaMLfRDKA96I9DOJperRQs8ZQZ1fJcyW9QZfVw8PMxBTXvQli', 'user', NULL, NULL);
ALTER TABLE `file`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `serverName` (`serverName`);

ALTER TABLE `folder`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_folder_name` (`userId`,`parentId`,`name`) USING BTREE,
  ADD KEY `parentId` (`parentId`);

ALTER TABLE `reset_password`
  ADD PRIMARY KEY (`id`),
  ADD KEY `userId` (`userId`);

ALTER TABLE `share`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `userId` (`userId`,`fileId`),
  ADD KEY `fileId` (`fileId`);

ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `file`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=0;

ALTER TABLE `folder`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `reset_password`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=0;

ALTER TABLE `share`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=0;

ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `reset_password`
  ADD CONSTRAINT `reset_password_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `share`
  ADD CONSTRAINT `share_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `share_ibfk_2` FOREIGN KEY (`fileId`) REFERENCES `file` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;
COMMIT;
