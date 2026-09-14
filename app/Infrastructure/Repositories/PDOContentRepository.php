<?php

namespace App\Infrastructure\Repositories;

use App\Infrastructure\Database\Connection;
use PDO;

class PDOContentRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
    }

    public function getHeroContent(): array
    {
        try {
            $stmt = $this->pdo->query("SELECT * FROM hero_content ORDER BY id ASC LIMIT 1");
            $res = $stmt ? $stmt->fetch() : null;
            return $res ?: [
                'title' => 'A Arte da<br>Precisão Facial',
                'subtitle' => 'Resultados que transcendem o tempo. Harmonização sofisticada com o rigor e a excelência que sua beleza merece.',
                'button_text' => 'Agendar Consulta',
                'button_link' => '/contato',
                'bg_image_dark' => '/assets/images/hero.png',
                'bg_image_light' => '/assets/images/hero_light.png'
            ];
        } catch (\Throwable $e) {
            return [
                'title' => 'A Arte da<br>Precisão Facial',
                'subtitle' => 'Resultados que transcendem o tempo. Harmonização sofisticada com o rigor e a excelência que sua beleza merece.',
                'button_text' => 'Agendar Consulta',
                'button_link' => '/contato',
                'bg_image_dark' => '/assets/images/hero.png',
                'bg_image_light' => '/assets/images/hero_light.png'
            ];
        }
    }

    public function updateHeroContent(array $data): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE hero_content 
            SET title = ?, subtitle = ?, button_text = ?, button_link = ?, bg_image_dark = ?, bg_image_light = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = 1
        ");
        return $stmt->execute([
            $data['title'],
            $data['subtitle'],
            $data['button_text'],
            $data['button_link'],
            $data['bg_image_dark'],
            $data['bg_image_light']
        ]);
    }

    public function getClinicContent(): array
    {
        try {
            $stmt = $this->pdo->query("SELECT * FROM clinic_content ORDER BY id ASC LIMIT 1");
            $res = $stmt ? $stmt->fetch() : null;
            return $res ?: [
                'title' => 'Excelência em Detalhes',
                'subtitle' => 'O Rigor do Tempo,<br>A Beleza do Detalhe.',
                'paragraph_1' => 'Assim como a alta relojoaria exige precisão milimétrica e atenção aos mínimos detalhes, a estética facial de alto nível requer maestria, técnica e um olhar clínico inquestionável.',
                'paragraph_2' => 'O Dr. George Scapin alia a ciência médica à percepção artística, entregando resultados que respeitam sua identidade e elevam sua autoestima através de um padrão de qualidade irretocável.',
                'highlight_quote' => 'A verdadeira elegância está na naturalidade.',
                'image_url' => '/assets/images/clinic.png'
            ];
        } catch (\Throwable $e) {
            return [
                'title' => 'Excelência em Detalhes',
                'subtitle' => 'O Rigor do Tempo,<br>A Beleza do Detalhe.',
                'paragraph_1' => 'Assim como a alta relojoaria exige precisão milimétrica e atenção aos mínimos detalhes, a estética facial de alto nível requer maestria, técnica e um olhar clínico inquestionável.',
                'paragraph_2' => 'O Dr. George Scapin alia a ciência médica à percepção artística, entregando resultados que respeitam sua identidade e elevam sua autoestima através de um padrão de qualidade irretocável.',
                'highlight_quote' => 'A verdadeira elegância está na naturalidade.',
                'image_url' => '/assets/images/clinic.png'
            ];
        }
    }

    public function updateClinicContent(array $data): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE clinic_content 
            SET title = ?, subtitle = ?, paragraph_1 = ?, paragraph_2 = ?, highlight_quote = ?, image_url = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = 1
        ");
        return $stmt->execute([
            $data['title'],
            $data['subtitle'],
            $data['paragraph_1'],
            $data['paragraph_2'],
            $data['highlight_quote'],
            $data['image_url']
        ]);
    }
}
