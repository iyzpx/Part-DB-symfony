<?php
/**
 *
 * part-db version 0.1
 * Copyright (C) 2005 Christoph Lechner
 * http://www.cl-projects.de/
 *
 * part-db version 0.2+
 * Copyright (C) 2009 K. Jacobs and others (see authors.php)
 * http://code.google.com/p/part-db/
 *
 * Part-DB Version 0.4+
 * Copyright (C) 2016 - 2019 Jan Böhmer
 * https://github.com/jbtronics
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
 *
 */

namespace App\Form\AdminPages;


use App\Entity\Base\NamedDBElement;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;

class CurrencyAdminForm extends BaseEntityAdminForm
{
    protected function additionalFormElements(FormBuilderInterface $builder, array $options, NamedDBElement $entity)
    {
        $is_new = $entity->getID() === null;

        $builder->add('iso_code', CurrencyType::class , ['required' => true,
            'label' => 'currency.iso_code.label',
            'preferred_choices' => ['EUR', 'USD', 'GBP', 'JPY', 'CNY'],
            'attr' => ['class' => 'selectpicker', 'data-live-search' => true],
            'disabled' => !$this->security->isGranted($is_new ? 'create' : 'edit', $entity)]);

        $builder->add('exchange_rate', MoneyType::class, ['required' => false,
            'label' => 'currency.exchange_rate.label', 'currency' => $this->params->get('default_currency'),
            'disabled' => !$this->security->isGranted($is_new ? 'create' : 'edit', $entity)]);
    }
}