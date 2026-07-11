<?php
/**
 * Znovupoužitelné pole pro nahrání fotky - custom tlačítko + živý náhled.
 * -----------------------------------------------------------------------------
 * Nahrazuje ošklivé nativní "Vybrat soubor" hezkým tlačítkem ve stylu webu
 * a ukazuje náhled budoucí i stávající fotky. Tvar náhledu (kulatý/hranatý)
 * se řídí parametrem.
 *
 * Použití ve formuláři:
 *   scm_photo_upload_field($baseUrl, $currentPhoto, 'round');   // hráči, trenéři
 *   scm_photo_upload_field($baseUrl, $currentPhoto, 'square');  // tréninky
 *
 * @param string $baseUrl     kořen webu (pro sestavení URL fotky)
 * @param string $currentPhoto cesta k současné fotce (relativně k public/) nebo ''
 * @param string $shape       'round' (kulatý) nebo 'square' (hranatý náhled)
 */
function scm_photo_upload_field(string $baseUrl, string $currentPhoto, string $shape = 'round'): void
{
    $squareClass = $shape === 'square' ? ' is-square' : '';
    $hasPhoto = $currentPhoto !== '';
    $currentUrl = $hasPhoto ? e($baseUrl . 'public/' . $currentPhoto) : '';
    // unikátní id, aby šlo mít víc polí na stránce bez kolize
    static $counter = 0;
    $counter++;
    $id = 'photoInput' . $counter;
    $previewId = 'photoPreview' . $counter;
    $nameId = 'photoName' . $counter;
    ?>
    <div class="photo-upload">
        <?php if ($hasPhoto): ?>
            <img id="<?= $previewId ?>" class="photo-preview<?= $squareClass ?>" src="<?= $currentUrl ?>" alt="Náhled fotky">
        <?php else: ?>
            <span id="<?= $previewId ?>" class="photo-preview is-empty<?= $squareClass ?>"><i class='bx bx-image-add'></i></span>
        <?php endif; ?>

        <div class="photo-upload-controls">
            <label class="photo-upload-btn">
                <i class='bx bx-upload'></i>
                <span>Vybrat fotku</span>
                <input type="file" name="photo" accept="image/*"
                       onchange="scmPhotoPreview(this, '<?= $previewId ?>', '<?= $nameId ?>', '<?= $squareClass !== '' ? 'square' : 'round' ?>')">
            </label>
            <span id="<?= $nameId ?>" class="photo-upload-name">
                <?= $hasPhoto ? 'Nahráním nové se stará nahradí.' : 'Zatím nevybráno.' ?>
            </span>
        </div>
    </div>
    <?php
}

/** Vytiskne JS pro živý náhled (stačí jednou na stránku, dá se volat vícekrát). */
function scm_photo_upload_script(): void
{
    static $printed = false;
    if ($printed) {
        return;
    }
    $printed = true;
    ?>
    <script>
        function scmPhotoPreview(input, previewId, nameId, shape) {
            var file = input.files && input.files[0];
            var preview = document.getElementById(previewId);
            var nameEl = document.getElementById(nameId);
            if (!file) {
                return;
            }
            // ukázat název souboru
            if (nameEl) {
                nameEl.textContent = file.name;
            }
            // vytvořit náhled z vybraného obrázku
            var reader = new FileReader();
            reader.onload = function (e) {
                // pokud je náhled zatím prázdný <span>, nahradíme ho <img>
                if (preview.tagName.toLowerCase() !== 'img') {
                    var img = document.createElement('img');
                    img.id = previewId;
                    img.className = 'photo-preview' + (shape === 'square' ? ' is-square' : '');
                    img.alt = 'Náhled fotky';
                    preview.parentNode.replaceChild(img, preview);
                    preview = img;
                }
                preview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    </script>
    <?php
}
