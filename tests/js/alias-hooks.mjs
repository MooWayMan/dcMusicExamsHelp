// tests/js/alias-hooks.mjs
//
// Lets node load the site's TypeScript as Vite does: '@/x' means
// resources/js/x, and an import without an extension means the .ts file.

import { statSync } from 'node:fs'
import path from 'node:path'
import { fileURLToPath, pathToFileURL } from 'node:url'

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../resources/js')

function isFile(file) {
  try {
    return statSync(file).isFile()
  } catch {
    return false
  }
}

function find(base) {
  for (const candidate of [base, `${base}.ts`, path.join(base, 'index.ts')]) {
    if (isFile(candidate)) return candidate
  }
  return null
}

export async function resolve(specifier, context, next) {
  if (specifier.startsWith('@/')) {
    const found = find(path.join(root, specifier.slice(2)))
    if (found) return next(pathToFileURL(found).href, context)
  }
  if ((specifier.startsWith('./') || specifier.startsWith('../')) && context.parentURL?.startsWith('file:')) {
    const found = find(path.resolve(path.dirname(fileURLToPath(context.parentURL)), specifier))
    if (found) return next(pathToFileURL(found).href, context)
  }
  return next(specifier, context)
}
