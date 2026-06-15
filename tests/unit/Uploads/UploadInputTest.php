<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Uploads;

use TheSaiged\Core\InvalidDataException;
use TheSaiged\Tests\TestCase;
use TheSaiged\Uploads\UploadInput;

/**
 * Construction is trivial (positional ctor on a readonly value object).
 * The interesting surface is `fromGlobals`: it has to translate the
 * $_FILES superglobal — across PHP's various UPLOAD_ERR_* states — into
 * either a clean UploadInput or an InvalidDataException.
 *
 * The "happy path" (UPLOAD_ERR_OK + valid file) needs `is_uploaded_file()`
 * which only returns true for files PHP actually received via SAPI —
 * unreachable in unit tests. That path is exercised by the live upload
 * smoke test outside this file. Here we cover the failure branches that
 * gate the is_uploaded_file check.
 */
final class UploadInputTest extends TestCase {

    function testConstructorPreservesAllFields (): void {
        $input = new UploadInput(
            tempPath:     '/tmp/upload',
            filename:     'photo.jpg',
            size:         1234,
            detectedMime: 'image/jpeg',
        );

        $this->assertSame('/tmp/upload', $input->tempPath);
        $this->assertSame('photo.jpg',   $input->filename);
        $this->assertSame(1234,          $input->size);
        $this->assertSame('image/jpeg',  $input->detectedMime);
    }

    function testFromGlobalsThrowsWhenFileKeyMissing (): void {
        $_FILES = [];
        $this->expectException(InvalidDataException::class);
        $this->expectExceptionMessageMatches('/expected multipart upload/i');
        UploadInput::fromGlobals();
    }

    function testFromGlobalsThrowsWhenFileEntryIsNotArray (): void {
        $_FILES = ['file' => 'not-an-array'];
        $this->expectException(InvalidDataException::class);
        UploadInput::fromGlobals();
    }

    function testFromGlobalsThrowsOnSizeLimit (): void {
        $_FILES = ['file' => ['error' => UPLOAD_ERR_INI_SIZE]];
        $this->expectException(InvalidDataException::class);
        $this->expectExceptionMessageMatches('/upload size limit/i');
        UploadInput::fromGlobals();
    }

    function testFromGlobalsThrowsOnFormSizeLimit (): void {
        $_FILES = ['file' => ['error' => UPLOAD_ERR_FORM_SIZE]];
        $this->expectException(InvalidDataException::class);
        $this->expectExceptionMessageMatches('/upload size limit/i');
        UploadInput::fromGlobals();
    }

    function testFromGlobalsThrowsOnOtherErrorCodes (): void {
        $_FILES = ['file' => ['error' => UPLOAD_ERR_PARTIAL]];
        $this->expectException(InvalidDataException::class);
        $this->expectExceptionMessageMatches('/upload failed/i');
        UploadInput::fromGlobals();
    }

    function testFromGlobalsThrowsWhenNoFileWasUploaded (): void {
        // Even with error=OK, an empty tmp_name string fails the
        // is_uploaded_file check — guards against test rigging.
        $_FILES = ['file' => ['error' => UPLOAD_ERR_OK, 'tmp_name' => '']];
        $this->expectException(InvalidDataException::class);
        UploadInput::fromGlobals();
    }

}
