# Politique de confidentialité et CGU (texte juridique intégré, étape 31)
C:\Users\alpho\Projets\lajanansanm\lajanansanm-etape31-textes-juridiques
Fichiers : resources/legal/privacy.md, resources/legal/terms.md (les textes), resources/views/legal/page.blade.php,
routes/legal.php (remplace l'ancien), tests/Feature/LegalPagesTest.php.
1. Copier les fichiers.
2. Dans routes/web.php : SUPPRIMER la ligne  Route::view('konfidansyalite', 'legal.privacy')->name('privacy');
   et garder  require __DIR__.'/legal.php';  juste avant « require __DIR__.'/auth.php'; ».
3. Supprimer resources/views/legal/privacy.blade.php et terms.blade.php (anciens brouillons, non utilisés).
4. php artisan route:clear && php artisan view:clear && php artisan test
5. Compléter tous les [CROCHETS] avant publication. Le nom « LajanAnsanm » a été harmonisé sur la marque de l'application.
