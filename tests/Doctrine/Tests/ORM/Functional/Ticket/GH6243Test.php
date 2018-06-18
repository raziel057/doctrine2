<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Tools\Pagination\Paginator;

class GH6243Test extends \Doctrine\Tests\OrmFunctionalTestCase
{

    protected function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\GH6243Role'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\GH6243Group'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\GH6243Menu'),
            ));
        } catch (\Exception $e) {

        }
    }

    public function testIssue()
    {
        $menu1 = new GH6243Menu();
        $menu1->setName("menu1");
        $this->_em->persist($menu1);

        $menu2 = new GH6243Menu();
        $menu2->setName("menu2");
        $this->_em->persist($menu1);

        $menu3 = new GH6243Menu();
        $menu3->setName("menu3");
        $this->_em->persist($menu1);

        $this->_em->flush();

        $admin = new GH6243Role();
        $admin->setName("admin");
        $admin->addMenu($menu1);
        $admin->addMenu($menu2);
        $admin->addMenu($menu3);
        $this->_em->persist($admin);
        $this->_em->flush();

        $basic = new GH6243Role();
        $basic->setName("basic");
        $basic->addMenu($menu1);
        $this->_em->persist($basic);

        $group1 = new GH6243Group();
        $group1->setName("group1");
        $group1->setRole($admin);
        $this->_em->persist($group1);

        $group2 = new GH6243Group();
        $group2->setName("group2");
        $group2->setRole($admin);
        $this->_em->persist($group2);

        $group3 = new GH6243Group();
        $group3->setName("group3");
        $group3->setRole($basic);
        $this->_em->persist($group3);

        $this->_em->flush();
        $this->_em->clear();

        $query = $this->_em->createQueryBuilder()
            ->select('r, g, count(m.id) as menuCount')
            ->from(GH6243Role::class, 'r')
            ->leftJoin('r.groups', 'g')
            ->leftJoin('r.menus', 'm')
            ->addGroupBy('r.id, g.id')
            ->getQuery()
            ->setHydrationMode(AbstractQuery::HYDRATE_ARRAY)
            ->setFirstResult(0)
            ->setMaxResults(40);

        $paginator = new Paginator($query, true);
        $count = count($paginator);

        $collection = $paginator->getIterator();

        $this->assertEquals(2, $count);
        $this->assertEquals(2, count($collection));

        $this->assertEquals('admin', $collection[0][0]['name']);
        $this->assertEquals(2, count($collection[0][0]['groups']));
        $this->assertEquals(3, $collection[0]['menuCount']);

        $this->assertEquals('basic', $collection[1][0]['name']);
        $this->assertEquals(1, count($collection[1][0]['groups']));
        $this->assertEquals(1, $collection[1]['menuCount']);
    }
}

/**
 * @Entity
 */
class GH6243Role
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @Column(type="string")
     */
    private $name;

    /**
     * @var \Doctrine\Common\Collections\Collection of \Menu
     *
     * @ManyToMany(targetEntity="GH6243Menu", indexBy="id", cascade={"persist"})
     */
    private $menus;

    /**
     * @OneToMany(targetEntity="GH6243Group", mappedBy="role", cascade={"persist"})
     */
    private $groups;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->menus = new \Doctrine\Common\Collections\ArrayCollection();
        $this->groups = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function addMenu(GH6243Menu $menu)
    {
        $this->menus[$menu->getId()] = $menu;

        return $this;
    }

    public function getGroups()
    {
        return $this->groups;
    }

    public function addGroup(GH6243Group $group)
    {
        $this->groups[$group->getId()] = $group;

        return $this;
    }
}

/**
 * @Entity
 */
class GH6243Group
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @Column(type="string")
     */
    private $name;

    /**
     * @var Role
     *
     * @ManyToOne(targetEntity="GH6243Role", inversedBy="groups")
     * @JoinColumns({
     *   @JoinColumn(name="role_id", referencedColumnName="id")
     * })
     */
    private $role;

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setRole(GH6243Role $role)
    {
        $this->role = $role;

        return $this;
    }

    public function getRole()
    {
        return $this->role;
    }
}

/**
 * @Entity
 */
class GH6243Menu
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @Column(type="string")
     */
    private $name;

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}
