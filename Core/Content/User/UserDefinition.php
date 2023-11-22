<?php

declare(strict_types=1);

namespace WebkulPOS\Core\Content\User;

use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\EmailField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\PasswordField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use WebkulPOS\Core\Content\Outlet\OutletDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CustomFields;

class UserDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'wkpos_user';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return UserCollection::class;
    }

    public function getEntityClass(): string
    {
        return UserEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            new FkField('avatar_id', 'avatarId', MediaDefinition::class),
            new FkField('outlet_id', 'outletId', OutletDefinition::class),
            (new StringField('username', 'username'))->addFlags(new Required()),
            (new PasswordField('password', 'password'))->addFlags(new Required()),
            (new StringField('first_name', 'firstName'))->addFlags(new Required()),
            (new StringField('last_name', 'lastName'))->addFlags(new Required()),
            (new EmailField('email', 'email'))->addFlags(new Required()),
            new BoolField('active', 'active'),
            new OneToManyAssociationField('media', MediaDefinition::class, 'user_id', 'id'),
            new ManyToOneAssociationField('outlet', 'outlet_id', OutletDefinition::class, 'id', false),
            new OneToOneAssociationField('avatarMedia', 'avatar_id', 'id', MediaDefinition::class),
          	new CustomFields(),
        ]);
    }
}
