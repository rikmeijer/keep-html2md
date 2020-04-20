<?php declare(strict_types=1);

$inDir = $_SERVER['argv'][1];
$outDir = $_SERVER['argv'][2];

foreach (glob($inDir . DIRECTORY_SEPARATOR . '*.html') as $htmlFile) {
    $html = DOMDocument::loadHTMLFile($htmlFile);
    if ($html === false) {
        print 'Unable to load HTML in ' . $htmlFile;
        continue;
    }

    $jsonFile = $inDir . DIRECTORY_SEPARATOR . basename($htmlFile, '.html') . '.json';
    $json = json_decode(file_get_contents($jsonFile));
    if ($json === null) {
        print 'Unable to load JSON in ' . $jsonFile;
        continue;
    }

    $title = $json->title;
    $content = $json->textContent;

    $labels = [];
    if ($json->isTrashed) {
        $labels[] = 'Trash';
    }

    if (property_exists($json, 'labels')) {
        foreach ($json->labels as $label) {
            $labels[] = $label->name;
        }
    }

    $attachments = [];
    if (property_exists($json, 'attachments')) {
        foreach ($json->attachments as $attachment) {
            $attachments[] = $attachment->filePath;

            $content .= "\n![". basename($attachment->filePath) . "](". basename($attachment->filePath) . ")";
        }
    }

    if (count($labels) === 0) {
        $labels[] = 'All';
    }

    foreach ($labels as $label) {
        if (is_dir($concurrentDirectory = $outDir . DIRECTORY_SEPARATOR . $label)) {

        } elseif (!mkdir($concurrentDirectory) && !is_dir($concurrentDirectory)) {
            print 'Target directory ' . $label . ' does not exist and can not be created';
            continue;
        }

        file_put_contents($concurrentDirectory . DIRECTORY_SEPARATOR . $title . '.md', $content);
        foreach ($attachments as $attachment) {
            if (file_exists($inDir . DIRECTORY_SEPARATOR . $attachment) === false) {
                $retry = $inDir . DIRECTORY_SEPARATOR . basename($attachment, '.jpeg') . '.jpg';
                if (file_exists($retry) === false) {
                    print 'Attachment ' . $attachment. ' for ' . $title . ' does not exist';
                    continue;
                }
                $attachment = basename($retry);
            } elseif (file_exists($concurrentDirectory . DIRECTORY_SEPARATOR . $attachment)) {
                unlink($concurrentDirectory . DIRECTORY_SEPARATOR . $attachment);
            }
            copy($inDir . DIRECTORY_SEPARATOR . $attachment, $concurrentDirectory . DIRECTORY_SEPARATOR . $attachment);
        }
    }
}

