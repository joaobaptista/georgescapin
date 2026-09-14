<?php

namespace App\Infrastructure\Services;

class ImageOptimizer
{
    /**
     * Otimiza uma imagem enviada: redimensiona para proporções ideais e converte para WebP em alta definição.
     *
     * @param string $sourcePath Caminho do arquivo temporário original
     * @param string $destinationPath Caminho de destino (com extensão .webp)
     * @param int $maxWidth Largura máxima recomendada (padrão: 1400px)
     * @param int $maxHeight Altura máxima recomendada (padrão: 1000px)
     * @param int $quality Qualidade do WebP de 1 a 100 (padrão: 85 - balanço perfeito entre peso e nitidez)
     * @return bool
     */
    public static function processAndConvertToWebp(
        string $sourcePath,
        string $destinationPath,
        int $maxWidth = 1400,
        int $maxHeight = 1000,
        int $quality = 85
    ): bool {
        if (!file_exists($sourcePath) || !is_readable($sourcePath)) {
            return false;
        }

        $imageInfo = @getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }

        [$origWidth, $origHeight, $imageType] = $imageInfo;

        // Cria o resource de imagem a partir do tipo
        $sourceImage = null;
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $sourceImage = @imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = @imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_WEBP:
                $sourceImage = @imagecreatefromwebp($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = @imagecreatefromgif($sourcePath);
                break;
            case IMAGETYPE_BMP:
                $sourceImage = @imagecreatefrombmp($sourcePath);
                break;
            default:
                return false;
        }

        if (!$sourceImage) {
            return false;
        }

        // Correção automática de rotação EXIF (fotos tiradas pelo celular)
        if ($imageType === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            try {
                $exif = @exif_read_data($sourcePath);
                if (!empty($exif['Orientation'])) {
                    switch ($exif['Orientation']) {
                        case 3:
                            $sourceImage = imagerotate($sourceImage, 180, 0);
                            break;
                        case 6:
                            $sourceImage = imagerotate($sourceImage, -90, 0);
                            // Inverte dimensões
                            $temp = $origWidth;
                            $origWidth = $origHeight;
                            $origHeight = $temp;
                            break;
                        case 8:
                            $sourceImage = imagerotate($sourceImage, 90, 0);
                            // Inverte dimensões
                            $temp = $origWidth;
                            $origWidth = $origHeight;
                            $origHeight = $temp;
                            break;
                    }
                }
            } catch (\Throwable $e) {
                // Ignora erro de leitura de EXIF se houver
            }
        }

        // Cálculo das novas dimensões proporcionais
        $newWidth = $origWidth;
        $newHeight = $origHeight;

        if ($origWidth > $maxWidth || $origHeight > $maxHeight) {
            $ratioWidth = $maxWidth / $origWidth;
            $ratioHeight = $maxHeight / $origHeight;
            $ratio = min($ratioWidth, $ratioHeight);

            $newWidth = (int)round($origWidth * $ratio);
            $newHeight = (int)round($origHeight * $ratio);
        }

        // Cria a imagem final redimensionada em True Color
        $canvas = imagecreatetruecolor($newWidth, $newHeight);

        // Preserva transparência (para PNGs ou WebP com canal alpha)
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $newWidth, $newHeight, $transparent);

        // Redimensionamento de alta qualidade com interpolação bicúbica
        imagecopyresampled(
            $canvas,
            $sourceImage,
            0, 0, 0, 0,
            $newWidth,
            $newHeight,
            $origWidth,
            $origHeight
        );

        // Garante que o diretório de destino existe
        $destDir = dirname($destinationPath);
        if (!is_dir($destDir)) {
            @mkdir($destDir, 0777, true);
        }

        // Converte e salva no formato WebP otimizado
        $success = imagewebp($canvas, $destinationPath, $quality);

        // Libera memória do servidor
        imagedestroy($sourceImage);
        imagedestroy($canvas);

        if ($success) {
            @chmod($destinationPath, 0644);
        }

        return $success;
    }
}
