(() => {
  // Отменяем стандартную отправку формы, если она есть
  const form = document.getElementById("uploadForm");
  const fileProgress = document.getElementById("fileProgress");
  const textError = document.getElementById("textError");
  const folderIdInput = document.getElementById("folderId");

  form?.addEventListener("submit", (e) => {
    e.preventDefault();
  });

  const flow = new Flow({
    target: "/files/add", // Используйте относительный путь, если клиент и сервер находятся на одном домене
    chunkSize: 1 * 1024 * 1024, // Размер чанка (например, 1MB)
    testChunks: false, // Отключаем тестирование чанков
    // singleFile: true,
    query: (file, chunk) => {
      return {
        finalSize: file.size,
        folderId: folderIdInput.value,
      };
    },
  });

  // Назначаем кнопку для открытия диалога выбора файла и привязываем input (если он есть)
  // flow.assignBrowse(document.getElementById("uploadButton"));
  flow.assignBrowse(document.getElementById("uploadButton"));

  // Обработчик, который вызывается, когда файл(ы) выбраны
  flow.on("fileAdded", (file) => {
    console.log("Файл добавлен:", file.name);
    // Если событие filesSubmitted не вызывается автоматически, можно вызвать upload() здесь
    // flow.upload();
  });

  // Обработчик, который вызывается сразу после выбора всех файлов
  flow.on("filesSubmitted", (files) => {
    console.log("filesSubmitted:", files);
    flow.upload();
  });

  // Дополнительный обработчик для отладки начала загрузки
  flow.on("uploadStart", () => {
    console.log("Начало загрузки");
  });

  // Отслеживаем загрузку каждого чанка
  flow.on("chunkUploaded", (file, chunk) => {
    console.log(
      "Чанк " + chunk.chunkNumber + " из " + file.chunks.length + " загружен"
    );
  });

  // Отслеживаем общий прогресс
  flow.on("progress", () => {
    if (fileProgress) {
      fileProgress.textContent = `Прогресс загрузки: ${Math.floor(
        flow.progress() * 100
      )}%`;
    }
  });

  // Успешное завершение загрузки файла
  flow.on("fileSuccess", (file) => {
    if (fileProgress) {
      fileProgress.textContent = `${file.name}  был успешно загружен`;
    }
  });

  // Обработчик ошибок загрузки
  flow.on("error", (file, message) => {
    console.log("Ошибка при загрузке:", message);
  });

  flow.on("fileError", (file, errorData) => {
    if (textError) {
      textError.textContent = `Ошибка отправки файла ${file.name}: ${
        JSON.parse(errorData).data
      }`;
    }
  });
})();
