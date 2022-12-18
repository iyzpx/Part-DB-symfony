<?php
/*
 * This file is part of Part-DB (https://github.com/Part-DB/Part-DB-symfony).
 *
 *  Copyright (C) 2019 - 2022 Jan Böhmer (https://github.com/jbtronics)
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published
 *  by the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\DataTables;

use App\DataTables\Column\EntityColumn;
use App\DataTables\Column\LocaleDateTimeColumn;
use App\DataTables\Column\MarkdownColumn;
use App\DataTables\Column\SelectColumn;
use App\DataTables\Helpers\PartDataTableHelper;
use App\Entity\Attachments\Attachment;
use App\Entity\Parts\Part;
use App\Entity\ProjectSystem\ProjectBOMEntry;
use App\Services\EntityURLGenerator;
use App\Services\Formatters\AmountFormatter;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\ORM\SearchCriteriaProvider;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableTypeInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProjectBomEntriesDataTable implements DataTableTypeInterface
{
    protected TranslatorInterface $translator;
    protected PartDataTableHelper $partDataTableHelper;
    protected EntityURLGenerator $entityURLGenerator;
    protected AmountFormatter $amountFormatter;

    public function __construct(TranslatorInterface $translator, PartDataTableHelper $partDataTableHelper,
        EntityURLGenerator $entityURLGenerator, AmountFormatter $amountFormatter)
    {
        $this->translator = $translator;
        $this->partDataTableHelper = $partDataTableHelper;
        $this->entityURLGenerator = $entityURLGenerator;
        $this->amountFormatter = $amountFormatter;
    }


    public function configure(DataTable $dataTable, array $options)
    {
        $dataTable
            //->add('select', SelectColumn::class)
            ->add('picture', TextColumn::class, [
                'label' => '',
                'className' => 'no-colvis',
                'render' => function ($value, ProjectBOMEntry $context) {
                    if($context->getPart() === null) {
                        return '';
                    }
                    return $this->partDataTableHelper->renderPicture($context->getPart());
                },
            ])

            ->add('id', TextColumn::class, [
                'label' => $this->translator->trans('part.table.id'),
                'visible' => false,
            ])

            ->add('quantity', TextColumn::class, [
               'label' => $this->translator->trans('project.bom.quantity'),
                'className' => 'text-center',
                'render' => function ($value, ProjectBOMEntry $context) {
                    //If we have a non-part entry, only show the rounded quantity
                    if ($context->getPart() === null) {
                        return round($context->getQuantity());
                    }
                    //Otherwise use the unit of the part to format the quantity
                    return $this->amountFormatter->format($context->getQuantity(), $context->getPart()->getPartUnit());
                },
            ])

            ->add('name', TextColumn::class, [
                'label' => $this->translator->trans('part.table.name'),
                'orderable' => false,
                'render' => function ($value, ProjectBOMEntry $context) {
                    if($context->getPart() === null) {
                        return $context->getName();
                    }
                    if($context->getPart() !== null) {
                        $tmp = $this->partDataTableHelper->renderName($context->getPart());
                        if(!empty($context->getName())) {
                            $tmp .= '<br><b>'.htmlspecialchars($context->getName()).'</b>';
                        }
                        return $tmp;
                    }
                },
            ])

            ->add('description', MarkdownColumn::class, [
                'label' => $this->translator->trans('part.table.description'),
                'data' => function (ProjectBOMEntry $context) {
                    if($context->getPart() !== null) {
                        return $context->getPart()->getDescription();
                    }
                    //For non-part BOM entries show the comment field
                    return $context->getComment();
                },
            ])


            ->add('category', EntityColumn::class, [
                'label' => $this->translator->trans('part.table.category'),
                'property' => 'part.category',
            ])
            ->add('footprint', EntityColumn::class, [
                'property' => 'part.footprint',
                'label' => $this->translator->trans('part.table.footprint'),
            ])

            ->add('manufacturer', EntityColumn::class, [
                'property' => 'part.manufacturer',
                'label' => $this->translator->trans('part.table.manufacturer'),
            ])

            ->add('mountnames', TextColumn::class, [

            ])


            ->add('addedDate', LocaleDateTimeColumn::class, [
                'label' => $this->translator->trans('part.table.addedDate'),
                'visible' => false,
            ])
            ->add('lastModified', LocaleDateTimeColumn::class, [
                'label' => $this->translator->trans('part.table.lastModified'),
                'visible' => false,
            ])
        ;

        $dataTable->createAdapter(ORMAdapter::class, [
            'entity' => Attachment::class,
            'query' => function (QueryBuilder $builder) use ($options): void {
                $this->getQuery($builder, $options);
            },
            'criteria' => [
                function (QueryBuilder $builder) use ($options): void {
                    $this->buildCriteria($builder, $options);
                },
                new SearchCriteriaProvider(),
            ],
        ]);
    }

    private function getQuery(QueryBuilder $builder, array $options): void
    {
        $builder->select('bom_entry')
            ->addSelect('part')
            ->from(ProjectBOMEntry::class, 'bom_entry')
            ->leftJoin('bom_entry.part', 'part')
            ->where('bom_entry.project = :project')
            ->setParameter('project', $options['project']);
        ;
    }

    private function buildCriteria(QueryBuilder $builder, array $options): void
    {

    }
}