<?php

namespace App\Services;

use App\Helpers\FileUploader;
use App\Models\Newsletter;
use App\Models\NewsletterPdf;
use App\Models\User;
use App\Notifications\NewNewsletterCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\PdfToText\Pdf;

/**
 * This class will be used for operations
 * performed on address model
 */
class NewsletterService
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getNewsletters(Request $request)
    {
        $newsletter = Newsletter::with('newsletter_pdfs');

        $year = $request->get('year');

        if ($year) {
            $newsletter->whereYear('date', $year);
        }

        return $newsletter->orderBy('date', 'desc')->get();
    }

    /**
     * Delete newsletters
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function destroyNewsletter($id)
    {
        try {
            $newsletter = Newsletter::find($id);


            //delete file from server
            $uploader = new FileUploader();
            $folder = "docs/newsletters/years/";
            $year = date_format(date_create($newsletter->date), 'Y');
            $folder = $folder . $year . '/';


            $file = count($newsletter->newsletter_pdfs) != 0 ? $folder . $newsletter->newsletter_pdfs[0]->path : null;

            if (config('app.file_upload') === "local") {
                if ($file !== null) {
                    $uploader->deleteFromStorage($file);
                };
            } else {
                if ($file !== null) {
                    $uploader->deleteFromBucket($file);
                }
            }

            //delete file from db
            $newsletter->newsletter_pdfs()->delete();
            $newsletter->delete();

            return response()->json('Newsletter deleted', 200);

        } catch (\Exception $e) {
            \Log::info($e->getMessage());
        }
    }

    /**
     * Bulk delete newsletters
     *
     * @param $ids
     */
    public function bulkDestroyNewsletters($ids)
    {
        try {
            $newsletters = Newsletter::whereIn('id', $ids)->get();

            foreach ($newsletters as $key => $newsletter) {
                $this->destroyNewsletter($newsletter->id);
            }
        } catch (\Exception $e) {
            \Log::info($e->getMessage());
        }
    }

    /**
     * Create newsletter
     *
     * @param array $data
     * @return \Illuminate\Http\Response
     */
    public function createNewsletter($data)
    {
        $data["user_id"] = auth()->user()->id;
        //create newsletter
        $newsletter = Newsletter::create($data);


        //insert document in NewsletterPdf
        //do it foreach if more than one documents

        //path
        $folder = "/docs/newsletters/years/";
        $year = date_format(date_create($newsletter->date), 'Y');
        $folder = $folder . $year . '/';

        $uploader = new FileUploader;

        if (config('app.file_upload') === "local") {
            $fileToStore = $uploader->uploadInStorage($data['file'], $folder);
        } else {
            $fileToStore = $uploader->uploadInCloud($data['file'], $folder);
        }

        //data for newsleters_pdfs
        $path = $fileToStore;
        $file_text = (new Pdf(config('pdftotext.binpath')))
            ->setPdf($data['file'])
            ->text();

        $newsletter->newsletter_pdfs()->create([
            'path' => $path,
            'file_text' => $file_text,
        ]);

        $newsletter->save();

        //send email to aproved user for new newsletter

        if ($data['accept_newsletters'] == "true") {
            $this->sendNewNewsletterMail($newsletter);
        }

        return $newsletter->load('newsletter_pdfs');

    }

    /**
     * Update Newsletter
     *
     * @param $id
     * @param $data
     * @return mixed
     */
    public function updateNewsletter($id, $data)
    {

        $newsletter = Newsletter::find($id);

        //update newsletter data
        $newsletter->update([
            'title' => $data['title'],
            'description' => $data['description'],
            'date' => $data['date']
        ]);

        //Update newsletters documents
        //do it foreach if more than one documents
        if ($data['file'] !== "null") {

            $uploader = new FileUploader();

            $folder = "/docs/newsletters/years/";
            $year = date_format(date_create($newsletter->date), 'Y');
            $folder = $folder . $year . '/';

            $file = count($newsletter->newsletter_pdfs) != 0 ? $folder . $newsletter->newsletter_pdfs[0]->path : null;

            if (config('app.file_upload') === "local") {
                if ($file !== null) {
                    $uploader->deleteFromStorage($file);
                };
                $fileToStore = $uploader->uploadInStorage($data['file'], $folder);
            } else {
                if ($file !== null) {
                    $uploader->deleteFromBucket($file);
                }
                $fileToStore = $uploader->uploadInCloud($data['file'], $folder);
            }

            //data for newsleters_pdfs
            $path = $fileToStore;
            $file_text = (new Pdf(config('pdftotext.binpath')))
                ->setPdf($data['file'])
                ->text();

            $newsletter->newsletter_pdfs()->delete();

            $newsletter->newsletter_pdfs()->create([
                'path' => $fileToStore,
                'file_text' => $file_text,
            ]);

            $newsletter->save();
        }

        if ($data['accept_newsletters'] == "true") {
            $this->sendNewNewsletterMail($newsletter);
        }

        return $newsletter->load('newsletter_pdfs');
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getAvailableYears()
    {
        return Newsletter::select(\DB::raw('YEAR(date) as year'))->distinct()->orderBy('year', 'desc')->get();
    }

    /**
     * Send email to approved user when new newsletter created
     * @param $newsletter
     */
    public function sendNewNewsletterMail($newsletter)
    {
        $users = User::role('user')->where('status', 'approved')->where('settings->notification', true)->get();

        foreach ($users as $user) {
            try {
                $user->notify(new NewNewsletterCreated($user, $newsletter));
            } catch (\Exception $e) {
                \Log::info($e->getMessage());
            }

        }

    }
}
