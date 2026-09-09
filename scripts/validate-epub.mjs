// scripts/validate-epub.mjs
import { EpubCheck } from '@likecoin/epubcheck-ts';
import { Epub } from '@smoores/epub';
import { readFile } from 'node:fs/promises';

const filePath = process.argv[2];

if (!filePath) {
  console.log(JSON.stringify({ error: 'No file path provided' }));
  process.exit(1);
}

// Helper: pull a metadata value by its element type, e.g. 'dc:publisher'
function findMetadataValue(metadataEntries, type) {
  const entry = metadataEntries.find((m) => m.type === type);
  return entry ? entry.value ?? null : null;
}

try {
  const epubData = await readFile(filePath);

  // 1. Validate
  const validation = await EpubCheck.validate(epubData);

  // 2. Extract metadata
  let metadata = null;
  try {
    const epub = await Epub.from(filePath); // path or Uint8Array both work

    const [title, creators, language, publicationDate, rawMetadata] =
      await Promise.all([
        epub.getTitle(),
        epub.getCreators(),
        epub.getLanguage(),
        epub.getPublicationDate(),
        epub.getMetadata(),
      ]);

    metadata = {
      title: title ?? null,
      // getCreators() returns DcCreator[] objects, not plain strings
      author: creators.map((c) => c.name),
      language: language ? language.toString() : null,
      publisher: findMetadataValue(rawMetadata, 'dc:publisher'),
      date: publicationDate ? publicationDate.toISOString() : null,
      description: findMetadataValue(rawMetadata, 'dc:description'),
      identifier: findMetadataValue(rawMetadata, 'dc:identifier'),
    };

    await epub.close();
  } catch (metaErr) {
    metadata = { error: 'Could not extract metadata: ' + metaErr.message };
  }

  console.log(JSON.stringify({ ...validation, metadata }));
} catch (err) {
  console.log(JSON.stringify({ error: err.message }));
  process.exit(1);
}
