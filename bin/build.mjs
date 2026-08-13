#!/usr/bin/env node
/**
 * Builds the browser bundles.
 *
 * Two artifacts from two entry points:
 *
 *   confetti.iife.js   A drop-in <script>, exposing window.LaranailConfetti.
 *                      What the route, published and CDN delivery modes serve.
 *   confetti.esm.mjs   An ES module, for applications importing it into their
 *                      own build.
 *
 * Both bundle canvas-confetti, so the package works with no npm step on the
 * consumer's side, which is the whole point, since it is installed through
 * Composer.
 *
 * `legalComments: 'inline'` is load-bearing rather than stylistic. canvas-confetti
 * is ISC licensed, and that licence requires the copyright notice to travel with
 * the code. The previous build minified the notice away, which made every copy
 * of the bundle a distribution without attribution.
 */
import { readFileSync } from 'node:fs'
import { dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'

import * as esbuild from 'esbuild'

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..')
const dev = process.argv.includes('--dev')
const watch = process.argv.includes('--watch')

const pkg = JSON.parse(readFileSync(resolve(root, 'package.json'), 'utf8'))

const NOTICE =
  `/*! laranail/confetti v${pkg.version} | MIT (c) Simtabi LLC\n` +
  ` * Bundles canvas-confetti | ISC (c) 2020 Kiril Vatev | https://github.com/catdad/canvas-confetti\n` +
  ` * Full notice: resources/dist/LICENSE-canvas-confetti.txt\n` +
  ` */`

const shared = {
  bundle: true,
  // "browser", not "neutral": this is browser code, and neutral forces
  // mainFields gymnastics to resolve canvas-confetti at all.
  platform: 'browser',
  target: ['es2022'],
  treeShaking: true,
  minify: !dev,
  sourcemap: dev ? 'inline' : false,
  sourcesContent: dev,
  legalComments: 'inline',
  banner: { js: NOTICE },
  define: {
    'process.env.NODE_ENV': dev ? '"development"' : '"production"',
    __CONFETTI_VERSION__: JSON.stringify(pkg.version),
  },
  logLevel: 'info',
}

const targets = [
  {
    ...shared,
    entryPoints: [resolve(root, 'resources/js/index.iife.js')],
    outfile: resolve(root, 'resources/dist/confetti.iife.js'),
    format: 'iife',
    globalName: 'LaranailConfettiBundle',
  },
  {
    ...shared,
    entryPoints: [resolve(root, 'resources/js/index.esm.js')],
    outfile: resolve(root, 'resources/dist/confetti.esm.mjs'),
    format: 'esm',
  },
]

if (watch) {
  const contexts = await Promise.all(targets.map((options) => esbuild.context(options)))

  await Promise.all(contexts.map((context) => context.watch()))

  console.log('Watching resources/js ...')
} else {
  await Promise.all(targets.map((options) => esbuild.build(options)))

  console.log(`Built ${targets.length} bundle(s) into resources/dist.`)
}
