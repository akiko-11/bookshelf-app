<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Genre;
use Illuminate\Contracts\View\View;

class BookController extends Controller
{
    public function index(): View
    {
        $books = Book::with('genres')
            ->latest()
            ->paginate(10);

        return view('books.index', ['books' => $books]);
    }

    public function show(Book $book): View
    {
        $book->load([
            'genres',
            'reviews.user',
            'reviews.likedByUsers',
        ]);

        return view('books.show', ['book' => $book]);
    }

    public function create(): View
    {
        $genres = Genre::all();

        return view('books.create', ['genres' => $genres]);
    }
}
