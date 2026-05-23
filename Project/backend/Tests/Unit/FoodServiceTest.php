<?php

namespace backend\Tests\Unit;

use App\Dtos\Requests\CreateFoodRequest;
use App\Dtos\Requests\UpdateFoodRequest;
use App\Dtos\Responses\FoodResponse;
use App\Repositories\Interfaces\IFoodRepository;
use App\Services\FoodService;
use PHPUnit\Framework\TestCase;

class FoodServiceTest extends TestCase
{
    public function testCreateFoodSuccess(): void
    {
        $repositoryMock = $this->createMock(IFoodRepository::class);

        $request = new CreateFoodRequest(
            name: 'Гречка',
            calories: 110.0,
            proteins: 4.2,
            fats: 1.1,
            carbs: 21.3,
            createdBy: 1
        );

        $expectedResponse = new FoodResponse(
            id: 10,
            name: 'Гречка',
            calories: 110.0,
            proteins: 4.2,
            fats: 1.1,
            carbs: 21.3,
            createdBy: 1
        );

        $repositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($request)
            ->willReturn($expectedResponse);

        $service = new FoodService($repositoryMock);

        $result = $service->createFood($request);

        $this->assertSame($expectedResponse, $result);
    }

    public function testCreateFoodThrowsExceptionWhenNameIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Название продукта не может быть пустым');

        $repositoryMock = $this->createMock(IFoodRepository::class);
        $repositoryMock->expects($this->never())->method('save');

        $service = new FoodService($repositoryMock);

        $request = new CreateFoodRequest(
            name: '',
            calories: 100.0,
            proteins: 1.0,
            fats: 1.0,
            carbs: 1.0,
            createdBy: 1
        );

        $service->createFood($request);
    }

    public function testCreateFoodThrowsExceptionWhenCpfcIsNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('КБЖУ не может быть отрицательным');

        $repositoryMock = $this->createMock(IFoodRepository::class);
        $repositoryMock->expects($this->never())->method('save');

        $service = new FoodService($repositoryMock);

        $request = new CreateFoodRequest(
            name: 'Некорректный продукт',
            calories: -10.0,
            proteins: 1.0,
            fats: 1.0,
            carbs: 1.0,
            createdBy: 1
        );

        $service->createFood($request);
    }

    public function testGetDefaultFoodsReturnsRepositoryResult(): void
    {
        $repositoryMock = $this->createMock(IFoodRepository::class);

        $expectedFoods = [
            new FoodResponse(1, 'Яблоко', 52.0, 0.3, 0.2, 14.0),
            new FoodResponse(2, 'Банан', 89.0, 1.1, 0.3, 22.0),
        ];

        $repositoryMock
            ->expects($this->once())
            ->method('getAll')
            ->willReturn($expectedFoods);

        $service = new FoodService($repositoryMock);

        $this->assertSame($expectedFoods, $service->getDefaultFoods());
    }

    public function testSearchFoodsReturnsRepositoryResult(): void
    {
        $repositoryMock = $this->createMock(IFoodRepository::class);

        $expectedFoods = [
            new FoodResponse(1, 'Куриная грудка', 113.0, 23.6, 1.9, 0.4),
        ];

        $repositoryMock
            ->expects($this->once())
            ->method('search')
            ->with('Курица')
            ->willReturn($expectedFoods);

        $service = new FoodService($repositoryMock);

        $this->assertSame($expectedFoods, $service->searchFoods('Курица'));
    }

    public function testGetRecentFoodsReturnsRepositoryResult(): void
    {
        $repositoryMock = $this->createMock(IFoodRepository::class);

        $expectedFoods = [
            new FoodResponse(1, 'Рис', 130.0, 2.0, 0.0, 28.0),
        ];

        $repositoryMock
            ->expects($this->once())
            ->method('getRecentByUserId')
            ->with(5)
            ->willReturn($expectedFoods);

        $service = new FoodService($repositoryMock);

        $this->assertSame($expectedFoods, $service->getRecentFoods(5));
    }

    public function testGetProductByIdReturnsRepositoryResult(): void
    {
        $repositoryMock = $this->createMock(IFoodRepository::class);

        $expectedFood = new FoodResponse(
            id: 7,
            name: 'Овсянка',
            calories: 68.0,
            proteins: 2.5,
            fats: 1.5,
            carbs: 12.0
        );

        $repositoryMock
            ->expects($this->once())
            ->method('getById')
            ->with(7)
            ->willReturn($expectedFood);

        $service = new FoodService($repositoryMock);

        $this->assertSame($expectedFood, $service->getProductById(7));
    }

    public function testGetCustomFoodsReturnsRepositoryResult(): void
    {
        $repositoryMock = $this->createMock(IFoodRepository::class);

        $expectedFoods = [
            new FoodResponse(3, 'Моя каша', 120.0, 4.0, 2.0, 20.0, 9),
        ];

        $repositoryMock
            ->expects($this->once())
            ->method('getCustomByUserId')
            ->with(9)
            ->willReturn($expectedFoods);

        $service = new FoodService($repositoryMock);

        $this->assertSame($expectedFoods, $service->getCustomFoods(9));
    }

    public function testGetCustomFoodByIdSuccess(): void
    {
        $repositoryMock = $this->createMock(IFoodRepository::class);

        $food = new FoodResponse(
            id: 3,
            name: 'Мой продукт',
            calories: 200.0,
            proteins: 10.0,
            fats: 5.0,
            carbs: 20.0,
            createdBy: 4
        );

        $repositoryMock
            ->expects($this->once())
            ->method('getById')
            ->with(3)
            ->willReturn($food);

        $service = new FoodService($repositoryMock);

        $this->assertSame($food, $service->getCustomFoodById(3, 4));
    }

    public function testGetCustomFoodByIdThrowsExceptionWhenUserIsNotOwner(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Доступ запрещен: это не ваш продукт');

        $repositoryMock = $this->createMock(IFoodRepository::class);

        $food = new FoodResponse(
            id: 3,
            name: 'Чужой продукт',
            calories: 200.0,
            proteins: 10.0,
            fats: 5.0,
            carbs: 20.0,
            createdBy: 4
        );

        $repositoryMock
            ->expects($this->once())
            ->method('getById')
            ->with(3)
            ->willReturn($food);

        $service = new FoodService($repositoryMock);

        $service->getCustomFoodById(3, 99);
    }

    public function testUpdateCustomFoodSuccess(): void
    {
        $repositoryMock = $this->createMock(IFoodRepository::class);

        $existingFood = new FoodResponse(
            id: 3,
            name: 'Старый продукт',
            calories: 100.0,
            proteins: 5.0,
            fats: 2.0,
            carbs: 10.0,
            createdBy: 4
        );

        $request = new UpdateFoodRequest(
            id: 3,
            name: 'Новый продукт',
            proteins: 7.0,
            fats: 3.0,
            carbs: 12.0,
            calories: 130.0
        );

        $updatedFood = new FoodResponse(
            id: 3,
            name: 'Новый продукт',
            calories: 130.0,
            proteins: 7.0,
            fats: 3.0,
            carbs: 12.0,
            createdBy: 4
        );

        $repositoryMock
            ->expects($this->once())
            ->method('getById')
            ->with(3)
            ->willReturn($existingFood);

        $repositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($request)
            ->willReturn($updatedFood);

        $service = new FoodService($repositoryMock);

        $this->assertSame($updatedFood, $service->updateCustomFood($request, 4));
    }

    public function testUpdateCustomFoodThrowsExceptionWhenUserIsNotOwner(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Доступ запрещен: вы не можете редактировать этот продукт');

        $repositoryMock = $this->createMock(IFoodRepository::class);

        $existingFood = new FoodResponse(
            id: 3,
            name: 'Чужой продукт',
            calories: 100.0,
            proteins: 5.0,
            fats: 2.0,
            carbs: 10.0,
            createdBy: 4
        );

        $request = new UpdateFoodRequest(
            id: 3,
            name: 'Попытка изменения',
            proteins: 7.0,
            fats: 3.0,
            carbs: 12.0,
            calories: 130.0
        );

        $repositoryMock
            ->expects($this->once())
            ->method('getById')
            ->with(3)
            ->willReturn($existingFood);

        $repositoryMock->expects($this->never())->method('save');

        $service = new FoodService($repositoryMock);

        $service->updateCustomFood($request, 99);
    }

    public function testUpdateCustomFoodThrowsExceptionWhenNameIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Название продукта не может быть пустым');

        $repositoryMock = $this->createMock(IFoodRepository::class);

        $existingFood = new FoodResponse(
            id: 3,
            name: 'Мой продукт',
            calories: 100.0,
            proteins: 5.0,
            fats: 2.0,
            carbs: 10.0,
            createdBy: 4
        );

        $request = new UpdateFoodRequest(
            id: 3,
            name: '',
            proteins: 7.0,
            fats: 3.0,
            carbs: 12.0,
            calories: 130.0
        );

        $repositoryMock
            ->expects($this->once())
            ->method('getById')
            ->with(3)
            ->willReturn($existingFood);

        $repositoryMock->expects($this->never())->method('save');

        $service = new FoodService($repositoryMock);

        $service->updateCustomFood($request, 4);
    }

    public function testUpdateCustomFoodThrowsExceptionWhenCpfcIsNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('КБЖУ не может быть отрицательным');

        $repositoryMock = $this->createMock(IFoodRepository::class);

        $existingFood = new FoodResponse(
            id: 3,
            name: 'Мой продукт',
            calories: 100.0,
            proteins: 5.0,
            fats: 2.0,
            carbs: 10.0,
            createdBy: 4
        );

        $request = new UpdateFoodRequest(
            id: 3,
            name: 'Некорректный продукт',
            proteins: -7.0,
            fats: 3.0,
            carbs: 12.0,
            calories: 130.0
        );

        $repositoryMock
            ->expects($this->once())
            ->method('getById')
            ->with(3)
            ->willReturn($existingFood);

        $repositoryMock->expects($this->never())->method('save');

        $service = new FoodService($repositoryMock);

        $service->updateCustomFood($request, 4);
    }

    public function testDeleteCustomFoodSuccess(): void
    {
        $repositoryMock = $this->createMock(IFoodRepository::class);

        $food = new FoodResponse(
            id: 3,
            name: 'Мой продукт',
            calories: 100.0,
            proteins: 5.0,
            fats: 2.0,
            carbs: 10.0,
            createdBy: 4
        );

        $repositoryMock
            ->expects($this->once())
            ->method('getById')
            ->with(3)
            ->willReturn($food);

        $repositoryMock
            ->expects($this->once())
            ->method('delete')
            ->with(3)
            ->willReturn(true);

        $service = new FoodService($repositoryMock);

        $this->assertTrue($service->deleteCustomFood(3, 4));
    }

    public function testDeleteCustomFoodThrowsExceptionWhenUserIsNotOwner(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Доступ запрещен: вы не можете удалить этот продукт');

        $repositoryMock = $this->createMock(IFoodRepository::class);

        $food = new FoodResponse(
            id: 3,
            name: 'Чужой продукт',
            calories: 100.0,
            proteins: 5.0,
            fats: 2.0,
            carbs: 10.0,
            createdBy: 4
        );

        $repositoryMock
            ->expects($this->once())
            ->method('getById')
            ->with(3)
            ->willReturn($food);

        $repositoryMock->expects($this->never())->method('delete');

        $service = new FoodService($repositoryMock);

        $service->deleteCustomFood(3, 99);
    }
}
