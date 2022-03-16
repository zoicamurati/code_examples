<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Newsletter\UpdateNewsletterRequest;
use App\Http\Requests\Newsletter\StoreNewsletterRequest;
use App\Models\Newsletter;
use App\Services\NewsletterService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;


class NewsletterController extends Controller
{

    public function __construct(NewsletterService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return response()->json($this->service->getNewsletters($request), 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreNewsletterRequest $request)
    {
        $validated = $request->validated();

        $newsletter_data = [
            "file" => $request->file('file'),
            "title" => $request->get('title'),
            "description" => $request->get('description'),
            "date" => $request->get('date') !== "null" ? $request->get('date') : null,
            "accept_newsletters" => $request->get('accept_newsletters')
        ];

        return response()->json($this->service->createNewsletter($newsletter_data), 201);
    }



    /**
     * Display the specified resource.
     *
     * @param  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {

        $newsletter = \App\Models\Newsletter::with('newsletter_pdfs')->where('id', $id)->withPrevious()->withNext();

        return response()->json($newsletter->first(), 200);

    }

     /**
     * Display the specified resource.
     *
     * @param  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByYear(Request $request, $year)
    {
        $newsletter = \App\Models\Newsletter::select('id', 'title', 'date')->whereYear('date', '=', $year)->orderBy('date', 'desc');

        return response()->json($newsletter->get(), 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Newsletter $newsletter
     * @return \Illuminate\Http\Response
     */
    public function edit(Newsletter $newsletter)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Newsletter $newsletter
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateNewsletterRequest $request, $id)
    {
        $validated = $request->validated();

        return response()->json($this->service->updateNewsletter($id, $request->all()), 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        return response()->json($this->service->destroyNewsletter($id), 204);
    }


    /**
     * Bulk delete
     *
     * @param $ids
     */
    public function bulkDestroy(Request $request)
    {
        $ids = isset($request->ids) ? explode(',', $request->get('ids')) : [];
        return response()->json($this->service->bulkDestroyNewsletters($ids), 204);

    }


    /**
     * Display a listing of the available years.
     *
     * @return \Illuminate\Http\Response
     */
    public function getAvailableYears(Request $request)
    {

        return response()->json($this->service->getAvailableYears());
    }
}
