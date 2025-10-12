<?php

namespace App\Http\Controllers;

use App\Helpers\AppConstants;
use App\Models\JeopardyQuestion;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JeopardyQuestionController extends Controller
{
    use AuthorizesRequests;

    public function index(): Response
    {
        $this->authorize('viewAny', JeopardyQuestion::class);

        return Inertia::render('Jeopardy', []);
    }

    public function getBoard(): JsonResponse
    {
        $questions = JeopardyQuestion::whereNot('category','Bonus')->get();
        $categories = [];

        foreach ($questions as $question) {
            if (in_array($question->category, $categories)) {
                continue;
            }
            $categories[] = $question->category;
        }

        $board = [];
        $selectedCategoriesKeys = array_rand($categories, AppConstants::JEOPARDY_CATEGORY_NUM);
        $selectedCategories = [];
        foreach ($selectedCategoriesKeys as $selectedCategoryKey) {
            $selectedCategories[] = $categories[$selectedCategoryKey];
        }
        foreach ($selectedCategories as $category) {
            $categoryQuestions = JeopardyQuestion::where('category', $category)->get()
                ->random(AppConstants::JEOPARDY_QUESTION_PER_CATEGORY)
                ->sortBy('difficulty');

            $board[$category] = $categoryQuestions;
        }

        return response()->json(['board' => $board]);
    }

    public function getBonusQuestions(): JsonResponse
    {
        // Return all available bonus questions; front-end will use `subcategory` as label
        $questions = JeopardyQuestion::where('category', 'Bonus')
            ->orderBy('subcategory')
            ->orderBy('difficulty')
            ->get();

        return response()->json(['questions' => $questions]);
    }


    public function store(Request $request)
    {
        $this->authorize('create', JeopardyQuestion::class);

        $data = $request->validate([
            'question' => ['required'],
            'answer' => ['required'],
            'category' => ['required'],
            'difficulty' => ['required', 'integer'],
            'reference' => ['nullable'],
            'subcategory' => ['nullable', 'string'],
        ]);

        return JeopardyQuestion::create($data);
    }

    public function update(Request $request, JeopardyQuestion $jeopardyQuestion)
    {
        $this->authorize('update', $jeopardyQuestion);

        $data = $request->validate([
            'question' => ['required'],
            'answer' => ['required'],
            'category' => ['required'],
            'difficulty' => ['required', 'integer'],
            'reference' => ['nullable'],
            'subcategory' => ['nullable', 'string'],
        ]);

        $jeopardyQuestion->update($data);

        return $jeopardyQuestion;
    }

    public function destroy(JeopardyQuestion $jeopardyQuestion)
    {
        $this->authorize('delete', $jeopardyQuestion);

        $jeopardyQuestion->delete();

        return response()->json();
    }
}
