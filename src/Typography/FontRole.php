<?php declare(strict_types = 1);

namespace TheSaiged\Typography;

/**
 * The two places a custom font can be assigned — mirrors the --serif
 * (Heading) and --sans (Text) CSS custom properties in main.css. Adding a
 * third role is a code change (a new CSS variable + render-site update),
 * not something admins configure — see FontRole vs TypographyFace split.
 */
enum FontRole: string {

    case Heading = 'heading';
    case Text    = 'text';

}
