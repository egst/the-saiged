<?php declare(strict_types = 1);

namespace TheSaiged\Shell\Footer;

enum FooterItemKind: string {

    case Link       = 'link';
    case Text       = 'text';
    case Newsletter = 'newsletter';

}
