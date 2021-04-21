<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\RecursionTest;

use Rebing\GraphQL\Tests\TestCase;
use Rebing\GraphQL\Tests\Unit\RecursionTest\Inputs\AuthorInput;
use Rebing\GraphQL\Tests\Unit\RecursionTest\Inputs\BookInput;
use Rebing\GraphQL\Tests\Unit\RecursionTest\Inputs\PublisherInput;
use Rebing\GraphQL\Tests\Unit\RecursionTest\Mutations\SaveAuthor;
use Rebing\GraphQL\Tests\Unit\RecursionTest\Mutations\SavePublisher;

class RecursionTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('graphql.schemas.default', [
            'mutation' => [
                SaveAuthor::class,
                SavePublisher::class,
            ],
        ]);
        $app['config']->set('graphql.types', [
            AuthorInput::class,
            BookInput::class,
            PublisherInput::class,
        ]);
    }

    public function testInfiniteLoopDirectToOne(): void
    {
        $post = [
            'query' => 'mutation SaveAuthor($author: AuthorInput!) {
                SaveAuthor(author: $author)
            }',
            'variables' => [
                'author' => [
                    'name' => 'Some Author Name',
                    'bestSellingBook' => [
                        'name' => 'Book Name',
                    ],
                ],
            ],
            'operationName' => 'SaveAuthor',
        ];
        $response = $this->json('POST', '/graphql', $post);
        $response->assertJson(['data' => ['SaveAuthor' => true]]);
    }

    public function testInfiniteLoopDirectToMany(): void
    {
        $post = [
            'query' => 'mutation SaveAuthor($author: AuthorInput!) {
                SaveAuthor(author: $author)
            }',
            'variables' => [
                'author' => [
                    'name' => 'Some Author Name',
                    'books' => [
                        ['name' => 'Book Name'],
                    ],
                ],
            ],
            'operationName' => 'SaveAuthor',
        ];
        $response = $this->json('POST', '/graphql', $post);
        $response->assertJson(['data' => ['SaveAuthor' => true]]);
    }

    public function testInfiniteLoopIndirectToOne(): void
    {
        $post = [
            'query' => 'mutation SavePublisher($publisher: PublisherInput!) {
                SavePublisher(publisher: $publisher)
            }',
            'variables' => [
                'publisher' => [
                    'name' => 'Some Publisher Name',
                    'bestSellingAuthor' => [
                        'name' => 'Author Name',
                        'bestSellingBook' => [
                            'name' => 'Book Name',
                        ],
                    ],
                ],
            ],
            'operationName' => 'SavePublisher',
        ];
        $response = $this->json('POST', '/graphql', $post);
        $response->assertJson(['data' => ['SavePublisher' => true]]);
    }

    public function testInfiniteLoopIndirectToMany(): void
    {
        $post = [
            'query' => 'mutation SavePublisher($publisher: PublisherInput!) {
                SavePublisher(publisher: $publisher)
            }',
            'variables' => [
                'publisher' => [
                    'name' => 'Some Publisher Name',
                    'authors' => [
                        [
                            'name' => 'Author Name',
                            'books' => [
                                ['name' => 'Book Name'],
                            ],
                        ],
                    ],
                ],
            ],
            'operationName' => 'SavePublisher',
        ];
        $response = $this->json('POST', '/graphql', $post);
        $response->assertJson(['data' => ['SavePublisher' => true]]);
    }
}
