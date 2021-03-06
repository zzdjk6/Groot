<?php

namespace Model;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\File;
use SilverStripe\Core\Path;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;

/**
 * Class BatchUploadJob
 * @package Model
 *
 * @property int TotalNumberOfFiles
 * @property int ProcessedNumberOfFiles
 * @property string Status
 * @property string Remark
 *
 * @method ArrayList StreamFiles
 * @method Playlist Playlist
 */
class BatchUploadJob extends DataObject
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_PROCESSING = 'PROCESSING';
    public const STATUS_ERROR = 'ERROR';
    public const STATUS_FINISHED = 'FINISHED';

    private static $table_name = 'BatchUploadJob';

    private static $db = [
        'TotalNumberOfFiles'     => 'Int',
        'ProcessedNumberOfFiles' => 'Int',
        'Status'                 => 'Enum("PENDING, PROCESSING, ERROR, FINISHED")',
        'Remark'                 => 'Text'
    ];

    private static $many_many = [
        'StreamFiles' => File::class
    ];

    private static $has_one = [
        'Playlist' => Playlist::class
    ];

    private static $summary_fields = [
        'ID',
        'TotalNumberOfFiles',
        'ProcessedNumberOfFiles',
        'Status'
    ];

    public function getCMSFields()
    {
        if (!$this->isInDB()) {
            return $this->getCMSFieldsForCreate();
        }

        return $this->getCMSFieldsForEdit();
    }

    private function getCMSFieldsForCreate()
    {
        $fields = FieldList::create(TabSet::create('Root'));

        $uploader = UploadField::create('StreamFiles', 'The Song Files');
        $uploader->setFolderName(Song::MP3_FOLDER_NAME);
        $uploader->getValidator()->setAllowedExtensions(['mp3']);
        $uploader->getValidator()->setAllowedMaxFileSize('1024M');
        $uploader->setAllowedMaxFileNumber(1000);

        $playlistDropDown = DropdownField::create(
            'PlaylistID',
            'Playlist (optional)',
            Playlist::get()->map('ID', 'Title'));
        $playlistDropDown->setEmptyString('(Choose a playlist)');

        $fields->addFieldsToTab('Root.Main', [
            $uploader,
            $playlistDropDown
        ]);

        return $fields;
    }

    private function getCMSFieldsForEdit()
    {
        $fields = FieldList::create(TabSet::create('Root'));

        $fields->addFieldsToTab('Root.Main', [
            NumericField::create('TotalNumberOfFiles')->setReadonly(true),
            NumericField::create('ProcessedNumberOfFiles')->setReadonly(true),
            TextField::create('Status')->setReadonly(true),
            TextField::create('Remark')->setReadonly(true)
        ]);

        if ($this->Playlist()) {
            $fields->addFieldToTab('Root.Main',
                DropdownField::create(
                    'PlaylistID',
                    'Playlist (optional)',
                    Playlist::get()->map('ID', 'Title'))->setDisabled(true)
            );
        }

        return $fields;
    }

    public function validate()
    {
        $result = parent::validate();

        $files = $this->StreamFiles() ?? new ArrayList();

        if ($files->count() === 0) {
            $result->addFieldError('StreamFiles', 'No files to be uploaded');
        }

        return $result;
    }

    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();

        $files = $this->StreamFiles() ?? new ArrayList();
        $this->record['TotalNumberOfFiles'] = $files->count();

        if ($this->Status === self::STATUS_PENDING) {
            $this->record['ProcessedNumberOfFiles'] = 0;
        }
    }

    protected function onAfterWrite()
    {
        parent::onAfterWrite();

        $id = $this->ID;
        $entryPoint = Path::join(BASE_PATH, 'vendor', 'silverstripe', 'framework', 'cli-script.php');
        $cmd = <<<CMD
nohup php \
$entryPoint \
dev/tasks/ProcessBatchUploadJobTask id=$id \
>/dev/null 2>&1 &
CMD;
        l($cmd);
        exec($cmd);
    }

}