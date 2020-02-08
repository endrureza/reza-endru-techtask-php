<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class LunchController extends AbstractController
{
    /**
     * Set today var
     *
     * @var string
     */
    public $today;

    public function __construct()
    {
        $this->today = date('Y-m-d');
    }

    /**
     * @Route("/lunch", name="recipe_lunch")
     *
     * @return void
     */
    public function recommendedRecipe()
    {
        $recipes = json_decode(file_get_contents(__DIR__ . '/../../data/recipe.json'))->recipes;

        $ingredients = json_decode(file_get_contents(__DIR__ . '/../../data/ingredient.json'))->ingredients;

        $edibleIngredients = $this->getEdibleIngredient($ingredients);

        $recommendedPriorityRecipes = [];

        $recommendedInferiorityRecipes = [];

        foreach ($recipes as $recipe) {
            if ($this->doesRecipeContainsAllIngredients($recipe->ingredients, array_column($edibleIngredients, 'title'))) {
                if ($this->doesRecipeContainsInferiorIngredients($recipe->ingredients, $this->makeTitleValueAsKeyArray($edibleIngredients))) {
                    $recommendedInferiorityRecipes[] = $recipe;
                } else {
                    $recommendedPriorityRecipes[] = $recipe;
                }
            }
        }

        return $this->json([
            'recipes' => array_merge($recommendedPriorityRecipes, $recommendedInferiorityRecipes),
        ]);
    }

    /**
     * Get only edible ingredient
     *
     * @param array $ingredients
     * @return array
     */
    protected function getEdibleIngredient(array $ingredients): array
    {
        $edibleIngredients = [];

        foreach ($ingredients as $ingredient) {
            if ($ingredient->{'use-by'} >= $this->today) {
                $edibleIngredients[] = $ingredient;
            }
        }

        return $edibleIngredients;
    }

    /**
     * Check whether recipe ingredient equals to given ingredients
     *
     * @param array $recipeIngredient
     * @param array $ingredients
     * @return bool
     */
    protected function doesRecipeContainsAllIngredients(array $recipeIngredient, array $ingredients): bool
    {
        $totalRecipeIngredient = count($recipeIngredient);

        $totalMatchIngredient = count(array_intersect($recipeIngredient, $ingredients));

        if ($totalRecipeIngredient != $totalMatchIngredient) {
            return false;
        }

        return true;
    }

    /**
     * Check whether there is at least one inferior ingredient
     *
     * @param array $recipeIngredient
     * @param array $ingredients
     * @return boolean
     */
    protected function doesRecipeContainsInferiorIngredients(array $recipeIngredient, array $ingredients): bool
    {
        foreach ($recipeIngredient as $ingredient) {
            $bestBefore = $ingredients[$ingredient]->{'best-before'};
            $useBy = $ingredients[$ingredient]->{'use-by'};

            if ($bestBefore < $this->today && $useBy > $this->today) {
                return true;
            }
        }

        return false;
    }

    /**
     * Turn value as key in array
     *
     * @param array $arr
     * @return array
     */
    protected function makeTitleValueAsKeyArray(array $arr): array
    {
        $newArr = [];

        foreach ($arr as $value) {
            $newArr[$value->title] = $value;
        }

        return $newArr;
    }
}
