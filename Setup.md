## Install Bootstrap via Vite
1. Install Bootstrap via npm
```bash
npm install boostrap
```
2. Import Bootstrap in your main CSS file
```Css
@import "bootstrap/dist/css/bootstrap.min.css"';
```
3. Import Bootstrap JS in your main JS file
in "resources/js/app.js"
```Js
import 'bootstrap';
```
4. Ensure Vite is compiling your assets

In vite.config.js, make sure the input paths are correct:

```Js
export default defineConfig({
    plugins: [laravel({
        input: ['resources/css/app.css', 'resources/js/app.js'],
        refresh: true,
    })],
});
```
5. Run Vite dev server
```Bash
npm run dev
```
6. Include compiled assets in your Blade layout
   
In resources/views/layouts/app.blade.php:
```Blade
@vite(['resources/css/app.css', 'resources/js/app.js'])
```
