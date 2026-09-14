<?php declare(strict_types = 1);

namespace TheSaiged\Uploads;

enum UploadKind: string {

    case Image = 'image';
    case Video = 'video';

}
