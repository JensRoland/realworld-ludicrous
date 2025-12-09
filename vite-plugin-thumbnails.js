import sharp from 'sharp'
import { readdir, mkdir } from 'fs/promises'
import { join, parse } from 'path'

/**
 * Vite plugin to generate thumbnail images from avatars.
 * Creates 32x32px AVIF thumbnails with '-thumb' suffix.
 */
export default function thumbnails(options = {}) {
  const {
    inputs = [
      { dir: 'app/public/img/avatars', pattern: /\.(avif|jpg|jpeg|png|webp)$/i },
      { dir: 'app/public/img', pattern: /^smiley-cyrus\.(avif|jpg|jpeg|png|webp)$/i },
    ],
    size = 32,
    suffix = '-thumb',
  } = options

  return {
    name: 'vite-plugin-thumbnails',

    async buildStart() {
      console.log(`\n🖼️  Generating ${size}x${size} thumbnails...`)

      let generated = 0

      for (const { dir, pattern } of inputs) {
        const files = await readdir(dir)
        const images = files.filter(f => pattern.test(f) && !f.includes(suffix))

        for (const file of images) {
          const { name } = parse(file)
          const inputPath = join(dir, file)
          const outputPath = join(dir, `${name}${suffix}.avif`)

          try {
            await sharp(inputPath)
              .resize(size, size, { fit: 'cover', position: 'top' })
              .avif({ quality: 70 })
              .toFile(outputPath)
            generated++
          } catch (err) {
            console.warn(`   ⚠️  Failed to process ${file}: ${err.message}`)
          }
        }
      }

      console.log(`   ✓ Generated ${generated} thumbnails\n`)
    }
  }
}
