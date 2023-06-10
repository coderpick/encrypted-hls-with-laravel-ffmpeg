<?php

namespace App\Console\Commands;

use FFMpeg\Format\Video\X264;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use ProtoneMedia\LaravelFFMpeg\Exporters\HLSVideoFilters;

class ProcessVideoUpload extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'video-upload:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert the uploaded video into HLS.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $lowFormat  = (new X264('aac'))->setKiloBitrate(500);
        $highFormat = (new X264('aac'))->setKiloBitrate(1000);

        $this->info('Converting redfield.mp4');

        FFMpeg::fromDisk('uploads')
            ->open('redfield.mp4')
            ->exportForHLS()
            ->withRotatingEncryptionKey(function($filename,$contents){                
                // Storage::disk('public')->put("videos/{$filename}",$contents);                
                Storage::disk('secrets')->put($filename,$contents);                
            })
            ->addFormat($lowFormat, function (HLSVideoFilters $filters) {
                $filters->resize(1280, 720);
            })
            ->addFormat($highFormat)
            ->onProgress(function ($progress) {
                $this->info("Progress: {$progress}%");
            })
            ->toDisk('public')
            ->save('videos/redfield.m3u8');

        $this->info('Done!');
    }
}
