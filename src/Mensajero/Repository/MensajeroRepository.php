<?php

declare(strict_types=1);

namespace App\Mensajero\Repository;

use App\Mensajero\Entity\Mensajero;
use App\Mensajero\Entity\EstadoAprobacion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Mensajero>
 */
class MensajeroRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Mensajero::class);
    }

    public function save(Mensajero $mensajero, bool $flush = false): void
    {
        $this->getEntityManager()->persist($mensajero);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Mensajero $mensajero, bool $flush = false): void
    {
        $this->getEntityManager()->remove($mensajero);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByUsuarioId(int $usuarioId): ?Mensajero
    {
        return $this->findOneBy(['usuario' => $usuarioId]);
    }

    /**
     * Busca mensajeros por estado de aprobación
     */
    public function findByEstadoAprobacion(EstadoAprobacion $estado, int $limit = 50): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.estadoAprobacion = :estado')
            ->setParameter('estado', $estado)
            ->innerJoin('m.usuario', 'u')
            ->addSelect('u')
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca mensajeros disponibles y aprobados
     */
    public function findDisponiblesYaprobados(int $limit = 20): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.disponible = true')
            ->andWhere('m.estadoAprobacion = :estado')
            ->setParameter('estado', EstadoAprobacion::APROBADO)
            ->andWhere('m.bloqueadoHasta IS NULL OR m.bloqueadoHasta < :now')
            ->setParameter('now', new \DateTime())
            ->innerJoin('m.usuario', 'u')
            ->addSelect('u')
            ->orderBy('m.calificacionPromedio', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Cuenta mensajeros por estado
     */
    public function countByEstadoAprobacion(EstadoAprobacion $estado): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.estadoAprobacion = :estado')
            ->setParameter('estado', $estado)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Busca mensajeros cercanos (sin cálculo geoespacial complejo, solo filtro básico)
     * Nota: Para cálculo real de distancia usar OSRM o fórmula Haversine en SQL personalizado
     */
    public function findCercanos(float $lat, float $lng, float $radioKm, int $limit = 20): array
    {
        // Implementación simplificada - en producción usar fórmula Haversine o PostGIS
        $qb = $this->createQueryBuilder('m')
            ->where('m.disponible = true')
            ->andWhere('m.estadoAprobacion = :estado')
            ->setParameter('estado', EstadoAprobacion::APROBADO)
            ->andWhere('m.bloqueadoHasta IS NULL OR m.bloqueadoHasta < :now')
            ->setParameter('now', new \DateTime())
            ->innerJoin('m.usuario', 'u')
            ->addSelect('u')
            ->setMaxResults($limit);

        // Nota: El filtrado por distancia real se hace en el servicio usando Redis o cálculos SQL
        return $qb->getQuery()->getResult();
    }

    /**
     * Incrementa el contador de rechazos consecutivos
     */
    public function incrementarRechazos(Mensajero $mensajero): void
    {
        $mensajero->setRechazosConsecutivos($mensajero->getRechazosConsecutivos() + 1);
        $this->save($mensajero, true);
    }

    /**
     * Resetea los rechazos consecutivos tras una aceptación
     */
    public function resetearRechazos(Mensajero $mensajero): void
    {
        $mensajero->setRechazosConsecutivos(0);
        $this->save($mensajero, true);
    }

    /**
     * Actualiza la calificación promedio
     */
    public function actualizarCalificacionPromedio(Mensajero $mensajero): void
    {
        // Este método debería ser llamado desde un servicio que calcule el promedio
        // La lógica real está en el servicio de calificaciones
        $this->save($mensajero, true);
    }

    /**
     * Incrementa el total de envíos completados
     */
    public function incrementarTotalEnvios(Mensajero $mensajero): void
    {
        $mensajero->setTotalEnvios($mensajero->getTotalEnvios() + 1);
        $this->save($mensajero, true);
    }
}
