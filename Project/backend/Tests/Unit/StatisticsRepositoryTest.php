<?php

namespace backend\Tests\Unit;

use App\Repositories\Implementations\StatisticsRepository;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class StatisticsRepositoryTest extends TestCase
{
    public function testSaveDailyNormExecutesInsert(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);

        $stmtMock
            ->expects($this->once())
            ->method('execute')
            ->with([
                'user_id' => 1,
                'calories' => 2300,
                'proteins' => 160.0,
                'fats' => 75.0,
                'carbs' => 260.0,
            ])
            ->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);

        $pdoMock
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $loggerMock = $this->createMock(LoggerInterface::class);

        $repository = new StatisticsRepository($pdoMock, $loggerMock);

        $repository->saveDailyNorm(1, 2300, 160.0, 75.0, 260.0);
    }

    public function testSaveUserParametersExecutesInsert(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);

        $stmtMock
            ->expects($this->once())
            ->method('execute')
            ->with([
                'user_id' => 1,
                'weight' => 80.0,
                'height' => 180,
                'activity_factor' => 'moderate',
                'goal' => 'maintain',
            ])
            ->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);

        $pdoMock
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $loggerMock = $this->createMock(LoggerInterface::class);

        $repository = new StatisticsRepository($pdoMock, $loggerMock);

        $repository->saveUserParameters(1, 80.0, 180, 'moderate', 'maintain');
    }

    public function testGetWeeklyProgressReturnsRows(): void
    {
        $expectedRows = [
            [
                'date' => '2026-05-18',
                'consumed_calories' => 1900,
                'consumed_proteins' => 130.0,
                'consumed_fats' => 60.0,
                'consumed_carbs' => 220.0,
            ],
        ];

        $stmtMock = $this->createMock(PDOStatement::class);

        $stmtMock
            ->expects($this->once())
            ->method('execute')
            ->with(['user_id' => 1])
            ->willReturn(true);

        $stmtMock
            ->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedRows);

        $pdoMock = $this->createMock(PDO::class);

        $pdoMock
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $loggerMock = $this->createMock(LoggerInterface::class);

        $repository = new StatisticsRepository($pdoMock, $loggerMock);

        $this->assertEquals($expectedRows, $repository->getWeeklyProgress(1));
    }

    public function testGetMonthlyProgressReturnsRows(): void
    {
        $expectedRows = [
            [
                'date' => '2026-05-01',
                'consumed_calories' => 2000,
                'consumed_proteins' => 140.0,
                'consumed_fats' => 65.0,
                'consumed_carbs' => 230.0,
            ],
        ];

        $stmtMock = $this->createMock(PDOStatement::class);

        $stmtMock
            ->expects($this->once())
            ->method('execute')
            ->with(['user_id' => 1])
            ->willReturn(true);

        $stmtMock
            ->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedRows);

        $pdoMock = $this->createMock(PDO::class);

        $pdoMock
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $loggerMock = $this->createMock(LoggerInterface::class);

        $repository = new StatisticsRepository($pdoMock, $loggerMock);

        $this->assertEquals($expectedRows, $repository->getMonthlyProgress(1));
    }

    public function testGetAveragesAndNormsReturnsFormattedData(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);

        $stmtMock
            ->expects($this->once())
            ->method('execute')
            ->with(['user_id' => 1])
            ->willReturn(true);

        $stmtMock
            ->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                'avg_calories' => 2100,
                'avg_proteins' => 140.456,
                'avg_fats' => 70.222,
                'avg_carbs' => 250.999,
                'target_calories' => 2300,
                'target_proteins' => 160.123,
                'target_fats' => 75.555,
                'target_carbs' => 260.777,
            ]);

        $pdoMock = $this->createMock(PDO::class);

        $pdoMock
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $loggerMock = $this->createMock(LoggerInterface::class);

        $repository = new StatisticsRepository($pdoMock, $loggerMock);

        $result = $repository->getAveragesAndNorms(1, 'month');

        $this->assertEquals(2100, $result['avg']['calories']);
        $this->assertEquals(140.5, $result['avg']['proteins']);
        $this->assertEquals(70.2, $result['avg']['fats']);
        $this->assertEquals(251.0, $result['avg']['carbs']);

        $this->assertEquals(2300, $result['target']['calories']);
        $this->assertEquals(160.1, $result['target']['proteins']);
        $this->assertEquals(75.6, $result['target']['fats']);
        $this->assertEquals(260.8, $result['target']['carbs']);
    }

    public function testGetAveragesAndNormsReturnsZerosWhenFetchIsEmpty(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);

        $stmtMock
            ->expects($this->once())
            ->method('execute')
            ->with(['user_id' => 1])
            ->willReturn(true);

        $stmtMock
            ->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);

        $pdoMock = $this->createMock(PDO::class);

        $pdoMock
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $loggerMock = $this->createMock(LoggerInterface::class);

        $repository = new StatisticsRepository($pdoMock, $loggerMock);

        $result = $repository->getAveragesAndNorms(1, 'week');

        $this->assertEquals(0, $result['avg']['calories']);
        $this->assertEquals(0.0, $result['avg']['proteins']);
        $this->assertEquals(0.0, $result['avg']['fats']);
        $this->assertEquals(0.0, $result['avg']['carbs']);

        $this->assertEquals(0, $result['target']['calories']);
        $this->assertEquals(0.0, $result['target']['proteins']);
        $this->assertEquals(0.0, $result['target']['fats']);
        $this->assertEquals(0.0, $result['target']['carbs']);
    }

    public function testGetLatestUserParametersReturnsData(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);

        $stmtMock
            ->expects($this->once())
            ->method('execute')
            ->with(['user_id' => 1])
            ->willReturn(true);

        $stmtMock
            ->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                'id' => 5,
                'user_id' => 1,
                'weight' => '80.55',
                'height' => 180,
                'activity_factor' => 'moderate',
                'goal' => 'maintain',
                'measured_at' => '2026-05-21 10:00:00',
            ]);

        $pdoMock = $this->createMock(PDO::class);

        $pdoMock
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $loggerMock = $this->createMock(LoggerInterface::class);

        $repository = new StatisticsRepository($pdoMock, $loggerMock);

        $result = $repository->getLatestUserParameters(1);

        $this->assertEquals(80.55, $result['weight']);
        $this->assertEquals(180, $result['height']);
        $this->assertEquals('moderate', $result['activityLevel']);
        $this->assertEquals('maintain', $result['goal']);
        $this->assertEquals('2026-05-21 10:00:00', $result['measuredAt']);
    }

    public function testGetLatestUserParametersReturnsNullWhenNotFound(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);

        $stmtMock
            ->expects($this->once())
            ->method('execute')
            ->with(['user_id' => 1])
            ->willReturn(true);

        $stmtMock
            ->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);

        $pdoMock = $this->createMock(PDO::class);

        $pdoMock
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $loggerMock = $this->createMock(LoggerInterface::class);

        $repository = new StatisticsRepository($pdoMock, $loggerMock);

        $this->assertNull($repository->getLatestUserParameters(1));
    }

    public function testGetLatestDailyNormReturnsData(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);

        $stmtMock
            ->expects($this->once())
            ->method('execute')
            ->with(['user_id' => 1])
            ->willReturn(true);

        $stmtMock
            ->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                'id' => 8,
                'user_id' => 1,
                'calories' => 2300,
                'proteins' => 160.44,
                'fats' => 75.55,
                'carbs' => 260.66,
                'created_at' => '2026-05-21 11:00:00',
            ]);

        $pdoMock = $this->createMock(PDO::class);

        $pdoMock
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $loggerMock = $this->createMock(LoggerInterface::class);

        $repository = new StatisticsRepository($pdoMock, $loggerMock);

        $result = $repository->getLatestDailyNorm(1);

        $this->assertEquals(2300, $result['calories']);
        $this->assertEquals(160.4, $result['proteins']);
        $this->assertEquals(75.6, $result['fats']);
        $this->assertEquals(260.7, $result['carbs']);
        $this->assertEquals('2026-05-21 11:00:00', $result['createdAt']);
    }

    public function testGetLatestDailyNormReturnsNullWhenNotFound(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);

        $stmtMock
            ->expects($this->once())
            ->method('execute')
            ->with(['user_id' => 1])
            ->willReturn(true);

        $stmtMock
            ->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);

        $pdoMock = $this->createMock(PDO::class);

        $pdoMock
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $loggerMock = $this->createMock(LoggerInterface::class);

        $repository = new StatisticsRepository($pdoMock, $loggerMock);

        $this->assertNull($repository->getLatestDailyNorm(1));
    }
}