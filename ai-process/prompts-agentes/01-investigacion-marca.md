# Investigación de la marca y el negocio de Optima Retail

- **Fase:** Planificación
- **Tipo de agente:** Explore
- **Qué aportó:** La paleta, la tipografía, el logo y el vocabulario de su formulario de incidencias; corrigió el supuesto inicial de que hacían software.

Encargo literal (las rutas de la máquina local están sustituidas por marcadores entre `< >`):

````text
READ-ONLY research task. Do not write or save any files anywhere (print everything to stdout only).

Goal: extract the visual brand and business context of the company Optima Retail from https://optimaretail.eu/ so a developer can replicate their look faithfully in a small Laravel + Tailwind web app (an "incident operations panel" built as a job-application technical test for them).

Do this:
1. WebFetch https://optimaretail.eu/ and its main subpages (about/company, product/solutions, contact, careers/jobs if present; also check /es/ or /en/ variants). Summarise: what the company does (product, customers, sector — likely retail software/AI for pricing, stock, assortment…), languages the site uses (Spanish/English/other), tone of voice, key taglines (quote them literally), and any mention of their tech stack, Laravel, PHP, AI.
2. Get the raw HTML with PowerShell (Invoke-WebRequest -UseBasicParsing, output to stdout only, never -OutFile) to find: the CMS/framework (WordPress/Elementor/Webflow/etc.), the stylesheet URLs, Google Fonts or self-hosted font families, the logo URL(s) (prefer SVG; give exact absolute URLs, also favicon/apple-touch-icon), og:image.
3. Fetch the main CSS files (stdout only; if huge, use Select-String on the content to pull hex/rgb colors, CSS custom properties like --e-global-color-*, --wp--preset--color-*, font-family declarations, border-radius, box-shadow, button styles). Produce a palette: primary, secondary, accent, dark/text, light backgrounds, with exact hex values and where each is used (buttons, headings, backgrounds, links). Note the typography (heading font, body font, weights), corner radius style, button style, spacing feel, use of gradients, imagery style (photos, illustrations, 3D, icons).
4. If the logo is an SVG, fetch it and report its viewBox, colours and whether it is a wordmark, symbol or both; if it is only PNG/WebP, report the URLs and sizes.
5. Search the web briefly (WebSearch) for "Optima Retail" Barcelona to confirm who they are (founding, product names, any job posting mentioning Laravel/PHP/AI programmer) — give source URLs.

Return a concise structured report: company summary; languages; tone; literal taglines; tech/CMS; logo asset URLs (absolute) with format; favicon URLs; palette table (name, hex, usage); typography (families + Google Fonts URL if any); UI style notes (radius, shadows, buttons, density); imagery; anything that would help design an internal ops dashboard in their style; sources.
````
