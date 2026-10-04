<?php

namespace App\Controller\Admin;

use App\Repository\GiftRepository;
use App\Repository\UserRepository;
use App\Services\Admin\AdminDashboardCharts;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private UserRepository $userRepository,
        private GiftRepository $giftRepository,
        private AdminDashboardCharts $charts,
    ) {
    }

    public function index(): Response
    {
        return $this->render('admin/my-dashboard.html.twig', [
            'userCount' => $this->userRepository->count(),
            'giftCount' => $this->giftRepository->count(),
            'chartDays' => AdminDashboardCharts::DAYS,
            'userChart' => $this->charts->usersCreatedPerDay(),
            'giftChart' => $this->charts->giftsCreatedPerDay(),
        ]);
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addAssetMapperEntry('admin')
        ;
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('My Gift')
        ;
    }

    public function configureCrud(): Crud
    {
        return parent::configureCrud()
            ->renderContentMaximized()
        ;
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToUrl('Site web', 'fa fa-globe', '/');
        yield MenuItem::linkTo(UserCrudController::class, 'Utilisateurs', 'fa fa-user');
        yield MenuItem::linkTo(GiftCrudController::class, 'Cadeaux', 'fa-solid fa-gift');
        yield MenuItem::linkTo(SectionCrudController::class, 'Sections', 'fa fa-list');
        yield MenuItem::linkTo(CrewCrudController::class, 'Groupes', 'fa fa-group');
    }
}
